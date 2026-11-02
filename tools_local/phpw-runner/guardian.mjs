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
const PERSIST = '/home/user/phpw/persist';
const APP_DB = '/home/user/appsync/database/database.sqlite';

// refreshApp() rebuilds /home/user/appsync from the checkout — which does not
// carry the runtime sqlite DB — so the seeded data must be saved out first and
// restored after. Without this every guardian restart wipes users/sales.
function persistSave() {
  try {
    if (fs.existsSync(APP_DB)) {
      fs.mkdirSync(PERSIST, { recursive: true });
      fs.copyFileSync(APP_DB, PERSIST + '/database.sqlite');
    }
  } catch (e) { console.log('[guardian] persist save failed: ' + e?.message); }
}
function persistRestore() {
  try {
    if (fs.existsSync(PERSIST + '/database.sqlite')) {
      fs.mkdirSync('/home/user/appsync/database', { recursive: true });
      fs.copyFileSync(PERSIST + '/database.sqlite', APP_DB);
      console.log('[guardian] database restored from persist');
    }
  } catch (e) { console.log('[guardian] persist restore failed: ' + e?.message); }
}

function refreshApp() {
  persistSave();
  try {
    fs.rmSync('/home/user/appsync', { recursive: true, force: true });
    fs.cpSync('/home/user/Z-Syst', '/home/user/appsync', { recursive: true });
    fs.rmSync('/home/user/appsync/vendor', { recursive: true, force: true });
    fs.rmSync('/home/user/appsync/node_modules', { recursive: true, force: true });
    console.log('[guardian] app snapshot refreshed');
  } catch (e) { console.log('[guardian] app refresh failed: ' + e?.message); }
  persistRestore();
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

// Periodic DB checkpoint so a killed child still loses nothing but the last
// few seconds of writes.
setInterval(persistSave, 15000);

refreshApp();
if (!healthy()) { repair(); }
start();
