/**
 * runner.mjs — run the Z-Syst Laravel app under PHP 8.3 WASM (@php-wasm/node).
 *
 * Modes:
 *   node runner.mjs artisan <args...>   console kernel with argv passthrough
 *   node runner.mjs file <vfs-path>     run a PHP script (diag/e2e helpers)
 *   node runner.mjs serve [port]        HTTP server (live preview, 0.0.0.0)
 *
 * Environment notes (learned the hard way, do not regress):
 *  - proc_open/shell_exec/exec MUST stay disabled: Symfony Console's Terminal
 *    stty probe calls shell_exec and deep C-stack in asyncify crashes WASM.
 *    (artisan `about` therefore cannot probe git/composer — expected.)
 *  - COLUMNS/LINES/TERM are set in the prelude so Symfony doesn't probe tty.
 *  - ext-intl is absent: a Normalizer wrapper class is eval'd over the
 *    symfony/polyfill-intl-normalizer implementation (constants inlined).
 *  - The console kernel is booted manually (reflection) because Application
 *    ::boot() deepens the C stack past the asyncify limit; BootProviders is
 *    replaced by an explicit per-provider boot loop.
 *  - HTTP: extension-less paths are rewritten in the bridge to Laravel's
 *    front controller (index.php) — real asset files are served as-is.
 */
import { loadNodeRuntime, createNodeFsMountHandler } from '@php-wasm/node';
import { PHP, setPhpIniEntries, PHPRequestHandler } from '@php-wasm/universal';
import http from 'node:http';
import fs from 'node:fs';

const VFS = '/syst';
const HOSTrepo = '/home/user/appsync';
const log = (...a) => console.log('[runner]', ...a);
const MODE = process.argv[2] || 'artisan';

/**
 * Compose the VFS the app expects (/syst) from stable pieces:
 *   /repo          -> the git checkout (NODEFS, live host files)
 *   /syst/*        -> symlinks into /repo (except vendor)
 *   /syst/vendor   -> NODEFS mount of /home/user/zvendor (golden copy)
 * The repo's own vendor/ is periodically pruned by the workspace snapshot
 * system (artifact caps), so the runtime must never read it.
 */
const SCAFFOLD = `
if (!is_dir('/syst')) { @mkdir('/syst'); }
$skip = ['vendor', 'storage', 'database', 'node_modules'];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('/repo', FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($it as $item) {
    $rel = substr($item->getPathname(), 6);
    $top = strtok($rel, '/');
    if (in_array($top, $skip, true)) { continue; }
    $t = '/syst/' . $rel;
    if ($item->isDir()) { @mkdir($t, 0777, true); }
    else { @copy($item->getPathname(), $t); }
}
// Persistent data stays on the host through the /repo mount:
@symlink('/repo/storage', '/syst/storage');
@symlink('/repo/database', '/syst/database');
@mkdir('/syst/vendor', 0777, true);
`;

async function mountApp(p) {
  await p.mount('/repo', createNodeFsMountHandler(HOSTrepo));
  await p.run({ code: '<?php ' + SCAFFOLD });
  await p.mount(VFS + '/vendor', createNodeFsMountHandler('/home/user/zvendor'));
}

log('booting PHP 8.3 (cold start ~30-90s)...');
const runtimeId = await loadNodeRuntime('8.3', { emscriptenOptions: { processId: 1 }, withIntl: true });
let php = new PHP();
await php.initializeRuntime(runtimeId);
await setPhpIniEntries(php, {
  memory_limit: '512M',
  disable_functions: 'shell_exec,exec,proc_open,system,passthru,proc_get_status,proc_nice',
  max_execution_time: '120',
  display_errors: '1',
});
await mountApp(php);
log(`repo mounted at ${VFS}`);

const PHP_PRELUDE = `
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
putenv('COLUMNS=140'); putenv('LINES=50'); putenv('TERM=dumb');
$_ENV['COLUMNS'] = '140'; $_ENV['LINES'] = '50'; $_ENV['TERM'] = 'dumb';
$_SERVER['COLUMNS'] = '140'; $_SERVER['LINES'] = '50'; $_SERVER['TERM'] = 'dumb';
date_default_timezone_set('UTC');
require '/syst/vendor/autoload.php';
if (!class_exists('Normalizer', false)) {
    eval('class Normalizer {
        const FORM_C = 4; const FORM_D = 2; const FORM_KC = 12; const FORM_KD = 6;
        const NFC = 4; const NFD = 2; const NFKC = 12; const NFKD = 6;
        public static function normalize(?string $s, ?int $f = 4) { return Symfony\\Polyfill\\Intl\\Normalizer\\Normalizer::normalize($s, $f); }
        public static function isNormalized(?string $s, ?int $f = 4) { return Symfony\\Polyfill\\Intl\\Normalizer\\Normalizer::isNormalized($s, $f); }
        public static function getRawDecomposition(string $s, ?int $f = 4) { return Symfony\\Polyfill\\Intl\\Normalizer\\Normalizer::getRawDecomposition($s, $f); }
    }');
}
`;

async function runArtisan(args) {
  const b64 = Buffer.from(JSON.stringify(args)).toString('base64');
  const code = `<?php
${PHP_PRELUDE}
$_SERVER['argv'] = array_merge(['artisan'], (array) json_decode(base64_decode('${b64}'), true));
$_SERVER['argc'] = count($_SERVER['argv']);
$_SERVER['SCRIPT_NAME'] = 'artisan';
$_SERVER['SCRIPT_FILENAME'] = '/syst/artisan';
chdir('/syst');
define('STDIN', fopen('php://stdin', 'r'));
require '/syst/artisan';
`;
  const res = await php.run({ code });
  return { out: res.text, exit: res.exitCode ?? 0, errors: res.errors };
}

if (MODE === 'artisan') {
  const args = process.argv.slice(3);
  const r = await runArtisan(args);
  process.stdout.write(r.out);
  if (r.errors) process.stderr.write(String(r.errors));
  process.exit(r.exit);
}

if (MODE === 'file') {
  const target = process.argv[3];
  const res = await php.run({ code: `<?php\n${PHP_PRELUDE}\nrequire ${JSON.stringify(target)};` });
  process.stdout.write(res.text);
  if (res.errors) process.stderr.write(String(res.errors));
  process.exit(res.exitCode ?? 0);
}

if (MODE === 'serve') {
  const port = Number(process.argv[3] || 8080);
  let booting = Promise.resolve();

  // Deep C-stack paths (e.g. failed bcrypt verify inside an HTTP request) can
  // abort the asyncify WASM instance. When that happens we transparently
  // reboot the runtime instead of leaving the preview dead.
  async function bootRuntime() {
    const runtimeId = await loadNodeRuntime('8.3', { emscriptenOptions: { processId: 1 }, withIntl: true });
    php = new PHP();
    await php.initializeRuntime(runtimeId);
    await mountApp(php);
    handler = new PHPRequestHandler({
      php,
      documentRoot: `${VFS}/public`,
      absoluteUrl: `http://localhost:${port}`,
    });
    log('runtime rebooted after fatal');
  }

  let handler = null;
  booting = bootRuntime();

  // A dying asyncify instance can still throw asynchronously (emscripten
  // timers firing after the abort) OUTSIDE the request try/catch — without
  // this hook the whole preview process dies. Reboot instead.
  process.on('uncaughtException', (e) => {
    const msg = String(e?.message ?? e);
    log(`uncaught (${msg.slice(0, 60)}); rebooting runtime...`);
    booting = bootRuntime().catch((be) => log('reboot failed: ' + be?.message));
  });

  const server = http.createServer(async (req, res) => {
    const chunks = [];
    for await (const c of req) chunks.push(c);
    const body = Buffer.concat(chunks);
    try {
      await booting;
      if (!handler) throw new Error('runtime not ready');
      const headers = { ...req.headers };
      delete headers.host; delete headers.connection;
      let url = req.url.startsWith('/') ? req.url : '/' + req.url;
      // Front-controller rewrite: extension-less paths go through Laravel's
      // index.php; real files (assets) are served as-is by the handler.
      if (!/^\/index\.php($|[/?#])/.test(url) && !/\.[a-zA-Z0-9]+($|[?#])/.test(url.split('?')[0])) {
        const [pathPart, queryPart] = url.split('?');
        url = '/index.php' + (pathPart === '/' ? '' : pathPart) + (queryPart ? '?' + queryPart : '');
      }
      const resp = await handler.request({
        url,
        method: req.method || 'GET',
        headers,
        body: body.length ? body : undefined,
      });
      const codes = resp.httpStatusCode ?? 200;
      const out = resp.bytes ?? new TextEncoder().encode(resp.text ?? '');
      // A PHP-level 500 with an EMPTY body means the asyncify instance is
      // wedged (zombie): real app errors always carry a body. Reboot now.
      if (codes >= 500 && out.length === 0) {
        log('empty 500 — bricked instance; rebooting runtime');
        booting = bootRuntime().catch((be) => log('reboot failed: ' + be?.message));
        res.writeHead(503, { 'content-type': 'application/json' });
        res.end(JSON.stringify({ message: 'Runtime rebooted. Please retry.' }));
        return;
      }
      res.writeHead(codes, Object.fromEntries(Object.entries(resp.headers ?? {}).map(([k, v]) => [k, Array.isArray(v) ? v.join(', ') : v])));
      res.end(out);
    } catch (e) {
      const msg = String(e?.message ?? e);
      if (/unreachable|abort|RuntimeError/i.test(msg)) {
        log(`fatal in request (${msg.slice(0, 60)}); rebooting runtime...`);
        booting = bootRuntime().catch((be) => log('reboot failed: ' + be?.message));
        res.writeHead(503, { 'content-type': 'application/json' });
        res.end(JSON.stringify({ message: 'Runtime rebooted after a fatal internal error. Please retry.' }));
        return;
      }
      res.writeHead(500, { 'content-type': 'text/plain' });
      res.end('runner error: ' + msg);
    }
  });
  server.listen(port, '0.0.0.0', () => log(`Z-Syst live on http://0.0.0.0:${port}`));
}
