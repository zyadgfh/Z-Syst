/**
 * guardian.mjs — keep the Z-Syst live preview alive against workspace
 * snapshot restores that intermittently wipe vendor/ to a mid-install state.
 *
 * Loop: if the canary file disappears (poisoned vendor), re-copy the golden
 * /home/user/zvendor into the repo and kill the server child so it restarts
 * with a fresh, healthy mount. Also restarts the child if it dies.
 */
import fs from 'node:fs';
import { spawn } from 'node:child_process';

const CANARY = '/home/user/zvendor/laravel/framework/src/Illuminate/Collections/functions.php';
const GOLDEN = '/home/user/zvendor';
const TARGET = '/home/user/Z-Syst/vendor';
const PHPW = '/home/user/phpw';

function refreshApp() {
  try {
    fs.rmSync('/home/user/appsync', { recursive: true, force: true });
    fs.cpSync('/home/user/Z-Syst', '/home/user/appsync', { recursive: true });
    fs.rmSync('/home/user/appsync/vendor', { recursive: true, force: true });
    fs.rmSync('/home/user/appsync/node_modules', { recursive: true, force: true });
    console.log('[guardian] app snapshot refreshed');
  } catch (e) { console.log('[guardian] app refresh failed: ' + e?.message); }
}
function healthy() {
  try { return fs.existsSync(CANARY); } catch { return false; }
}
function repair() {
  try { fs.rmSync(TARGET, { recursive: true, force: true }); } catch {}
  fs.cpSync(GOLDEN, TARGET, { recursive: true });
  console.log('[guardian] vendor repaired from golden copy');
}

let child = null;
let restarting = false;
function start() {
  restarting = false;
  child = spawn('node', ['runner.mjs', 'serve', '8080'], { cwd: PHPW, stdio: 'inherit' });
  child.on('exit', () => {
    if (!restarting) setTimeout(start, 1500);
  });
}

setInterval(() => {
  if (!healthy()) {
    console.log('[guardian] vendor poisoned — repairing and restarting server');
    restarting = true;
    repair();
    if (child) { try { child.kill('SIGKILL'); } catch {} }
    setTimeout(start, 1000);
  }
}, 4000);

refreshApp();
if (!healthy()) { repair(); }
start();
