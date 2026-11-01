#!/usr/bin/env python3
"""
vendor_install.py — install composer.lock dependencies without composer.

Downloads each package as a GitHub zipball (composer.lock dist URLs all
point at api.github.com) and generates a Composer-compatible autoloader:
  vendor/autoload.php
  vendor/composer/{ClassLoader,autoload_real,autoload_psr4,autoload_namespaces,
                   autoload_files,autoload_classmap,InstalledVersions}.php
  vendor/composer/installed.{json,php}

Hard-wired fixes learned from live runs (PHP 8.3 WASM):
  - root composer.json autoload entries come FIRST (pint also claims App\\)
  - PSR-4/PSR-0 paths are mapped relative to the REPO root via
    dirname(dirname(__DIR__)) (not vendor/composer/..)
  - files-autoload entries are absolute /syst/... VFS paths, comma-separated
  - thecodingmachine/safe shims load before anything that calls Safe\\*
  - InstalledVersions implements the real Composer 2 API (satisfies(parser,
    name, constraint)) using composer/semver
"""
import json
import os
import io
import time
import zipfile
import urllib.request
import subprocess

ROOT = "/home/user/Z-Syst"
VENDOR = os.path.join(ROOT, "vendor")
LOCK = os.path.join(ROOT, "composer.lock")


def token():
    try:
        return subprocess.check_output(["gh", "auth", "token"], text=True).strip()
    except Exception:
        return os.environ.get("GITHUB_TOKEN", "")


TOKEN = token()


def gh_json(url, retries=3):
    req = urllib.request.Request(url)
    req.add_header("User-Agent", "zsyst-vendor-installer")
    req.add_header("Accept", "application/vnd.github+json")
    if TOKEN:
        req.add_header("Authorization", f"Bearer {TOKEN}")
    for i in range(retries):
        try:
            with urllib.request.urlopen(req, timeout=120) as r:
                return json.loads(r.read())
        except Exception:
            if i == retries - 1:
                raise
            time.sleep(2 * (i + 1))


def gh_bytes(url, retries=3):
    req = urllib.request.Request(url)
    req.add_header("User-Agent", "zsyst-vendor-installer")
    if TOKEN:
        req.add_header("Authorization", f"Bearer {TOKEN}")
    for i in range(retries):
        try:
            with urllib.request.urlopen(req, timeout=180) as r:
                return r.read()
        except Exception:
            if i == retries - 1:
                raise
            time.sleep(2 * (i + 1))


def pj(path):
    with open(path, encoding="utf-8") as f:
        return json.load(f)


def install_package(p):
    name = p["name"]
    dest = os.path.join(VENDOR, *name.split("/"))
    if os.path.exists(os.path.join(dest, "composer.json")):
        return "cached"
    url = p["dist"]["url"]
    data = gh_bytes(url)
    zf = zipfile.ZipFile(io.BytesIO(data))
    members = zf.namelist()
    top = members[0].split("/")[0] if members else ""
    os.makedirs(dest, exist_ok=True)
    for m in members:
        rel = m[len(top) + 1:] if top and m.startswith(top + "/") else m
        if not rel or rel.endswith("/"):
            continue
        target = os.path.join(dest, *rel.split("/"))
        os.makedirs(os.path.dirname(target), exist_ok=True)
        with zf.open(m) as src, open(target, "wb") as out:
            out.write(src.read())
    return "installed"


def php_str(s):
    B = chr(92)
    return "'" + s.replace(B, B * 2).replace(chr(39), B + chr(39)) + "'"


def arr(items, indent="    "):
    if not items:
        return "[]"
    return "[\n" + ",\n".join(f"{indent}{i}" for i in items) + ",\n]"


PROD_ONLY = os.environ.get("VENDOR_WITH_DEV") != "1"  # prod-only by default


def collect_autoload():
    lock = pj(LOCK)
    packages = lock["packages"] + ([] if PROD_ONLY else lock.get("packages-dev", []))
    root = pj(os.path.join(ROOT, "composer.json"))
    ra = root.get("autoload", {})
    rad = root.get("autoload-dev", {})
    psr4, psr0, files, classmaps = {}, {}, [], []
    # ROOT project mappings come FIRST (packages like pint also claim App\)
    for ns, d in {**ra.get("psr-4", {}), **rad.get("psr-4", {})}.items():
        base = [d] if isinstance(d, str) else d
        psr4.setdefault(ns, [])
        for x in base:
            if x not in psr4[ns]:
                psr4[ns].append(x)
    for ns, d in {**ra.get("psr-0", {}), **rad.get("psr-0", {})}.items():
        base = [d] if isinstance(d, str) else d
        psr0.setdefault(ns, [])
        for x in base:
            if x not in psr0[ns]:
                psr0[ns].append(x)
    for f in list(ra.get("files", [])) + list(rad.get("files", [])):
        if f not in files:
            files.append(f)
    for c in list(ra.get("classmap", [])) + list(rad.get("classmap", [])):
        classmaps.append(("", c))
    installed = []
    total = len(packages)
    for idx, p in enumerate(packages, 1):
        name = p["name"]
        status = install_package(p)
        cj = pj(os.path.join(VENDOR, *name.split("/"), "composer.json"))
        a = cj.get("autoload", {})
        for ns, dirs in (a.get("psr-4") or {}).items():
            psr4.setdefault(ns, [])
            base = [dirs] if isinstance(dirs, str) else dirs
            for d in base:
                path = os.path.join("vendor", name, d)
                if path not in psr4[ns]:
                    psr4[ns].append(path)
        for ns, dirs in (a.get("psr-0") or {}).items():
            psr0.setdefault(ns, [])
            base = [dirs] if isinstance(dirs, str) else dirs
            for d in base:
                path = os.path.join("vendor", name, d)
                if path not in psr0[ns]:
                    psr0[ns].append(path)
        for f in (a.get("files") or []):
            fp = os.path.join("vendor", name, f)
            if fp not in files:
                files.append(fp)
        for c in (a.get("classmap") or []):
            classmaps.append((name, c))
        laravel = (cj.get("extra") or {}).get("laravel") or {}
        entry = {
            "name": name, "version": p.get("version"),
            "type": p.get("type", "library"),
            "install_path": os.path.join(VENDOR, *name.split("/")),
            "aliases": [], "reference": p.get("dist", {}).get("reference"),
        }
        if laravel:
            entry["extra"] = {"laravel": {
                "providers": list(laravel.get("providers") or []),
                "aliases": laravel.get("aliases") or {},
            }}
        installed.append(entry)
        print(f"[{idx}/{total}] {status:7s} {name}")
    verify_packages(packages)
    # dependency-order safeguard: Safe's function shims must load first
    files.sort(key=lambda f: (0 if "/safe/" in f else 1, f))
    return psr4, psr0, files, classmaps, installed


def verify_packages(packages):
    """Re-extract packages whose autoload dirs are missing (observed after
    a workspace snapshot restore wiped vendor/laravel/framework/src)."""
    for p in packages:
        name = p["name"]
        dest = os.path.join(VENDOR, *name.split("/"))
        cj_path = os.path.join(dest, "composer.json")
        if not os.path.exists(cj_path):
            install_package(p)
            continue
        try:
            a = pj(cj_path).get("autoload", {})
        except Exception:
            continue
        missing = False
        for kind in ("psr-4", "psr-0"):
            for dirs in (a.get(kind) or {}).items():
                base = dirs[1] if isinstance(dirs[1], str) else dirs[1]
                for d in base:
                    if not os.path.exists(os.path.join(dest, d)):
                        missing = True
                        break
                if missing:
                    break
            if missing:
                break
        if missing:
            print(f"[verify] re-extracting broken package {name}")
            import shutil
            shutil.rmtree(dest, ignore_errors=True)
            install_package(p)


CLASSLOADER = r'''<?php

namespace Composer\Autoload;

/**
 * Minimal Composer ClassLoader (PSR-4 + PSR-0 + classmap + files).
 * Generated by tools_local/vendor_install.py.
 */
class ClassLoader
{
    private $legacyRoots = array();
    private $prefixDirsPsr4 = array();
    private $fallbackDirsPsr0 = array();
    private $classMap = array();

    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    public function setPsr4($prefix, $paths) { $this->prefixDirsPsr4[rtrim($prefix, '\\') . '\\'] = $paths; }
    public function set($prefix, $paths)
    {
        if ($prefix) { $this->legacyRoots[$prefix] = $paths; }
        else { $this->fallbackDirsPsr0 = $paths; }
    }
    public function addClassMap($classmap) { $this->classMap = array_merge($this->classMap, $classmap); }

    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) { include $file; return true; }
        return false;
    }

    public function findFile($class)
    {
        if (isset($this->classMap[$class])) { return $this->classMap[$class]; }

        foreach ($this->prefixDirsPsr4 as $prefix => $dirs) {
            if (str_starts_with($class, $prefix)) {
                $rest = str_replace('\\', '/', substr($class, strlen($prefix)));
                foreach ($dirs as $dir) {
                    if (is_file($dir . '/' . $rest . '.php')) { return $dir . '/' . $rest . '.php'; }
                }
            }
        }
        foreach ($this->legacyRoots as $prefix => $dirs) {
            if (str_starts_with($class, $prefix)) {
                $rest = str_replace('\\', '/', substr($class, strlen($prefix)));
                foreach ($dirs as $dir) {
                    if (is_file($dir . '/' . $rest . '.php')) { return $dir . '/' . $rest . '.php'; }
                }
            }
        }
        foreach ($this->fallbackDirsPsr0 as $dir) {
            $rest = str_replace('\\', '/', $class);
            if (is_file($dir . '/' . $rest . '.php')) { return $dir . '/' . $rest . '.php'; }
        }
        foreach ($this->fallbackDirsPsr0 as $dir) {
            if (is_file($dir . '/' . str_replace('\\', '/', $class) . '.php')) {
                return $dir . '/' . str_replace('\\', '/', $class) . '.php';
            }
        }
        return null;
    }
}
'''

AUTOLOAD_REAL = r'''<?php

class ComposerAutoloaderInit
{
    private static $loader;

    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();
        spl_autoload_unregister(array('ComposerAutoloaderInit', 'loadClassLoader'));

        $map = require __DIR__ . '/autoload_psr4.php';
        foreach ($map as $ns => $paths) { $loader->setPsr4($ns, array_map(fn($p) => dirname(dirname(__DIR__)).'/'.$p, $paths)); }

        $map0 = require __DIR__ . '/autoload_namespaces.php';
        foreach ($map0 as $ns => $paths) { $loader->set($ns, array_map(fn($p) => dirname(dirname(__DIR__)).'/'.$p, $paths)); }

        $vendorDir = dirname(__DIR__);
        $classMap = require __DIR__ . '/autoload_classmap.php';
        if ($classMap) { $loader->addClassMap($classMap); }

        $loader->register(true);

        require __DIR__ . '/InstalledVersions.php';

        foreach (require __DIR__ . '/autoload_files.php' as $file) {
            require $file;
        }

        return $loader;
    }

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }
}
'''

INSTALLED_VERSIONS = r'''<?php
namespace Composer;

use Composer\Semver\VersionParser;

class InstalledVersions
{
    public static function getInstalledPackages(): array { return array_keys(self::data()['versions']); }
    public static function isInstalled(string $name, bool $includeDev = true): bool {
        $d = self::data(); return isset($d['versions'][$name]) || $name === ($d['root']['name'] ?? '');
    }
    public static function getVersion(string $name): ?string { $v = self::data()['versions'][$name] ?? null; return $v['version'] ?? null; }
    public static function getPrettyVersion(string $name): ?string { $v = self::data()['versions'][$name] ?? null; return $v['pretty_version'] ?? null; }
    public static function getReference(string $name): ?string { $v = self::data()['versions'][$name] ?? null; return $v['reference'] ?? null; }
    public static function getVersionRanges(string $name): array { return []; }
    public static function has($name) { return self::isInstalled((string) $name); }

    /**
     * Composer 2 signature: parser first, then package name + constraint.
     */
    public static function satisfies(VersionParser $parser, string $name, ?string $constraint = null): bool
    {
        if (!self::isInstalled($name)) { return false; }
        if ($constraint === null || $constraint === '') { return true; }
        $version = self::getVersion($name);
        if ($version === null) { return true; }
        try {
            $provided = $parser->parseConstraints(ltrim($version, 'vV'));
            $required = $parser->parseConstraints($constraint);
        } catch (\Throwable $e) { return true; }
        return $provided->matches($required);
    }

    public static function getRootPackage(): array { return self::data()['root'] ?? ['name' => 'zsyst/app', 'version' => '1.0.0']; }
    public static function getRawData(): array { return self::data(); }
    private static function data(): array { static $d = null; return $d ??= require __DIR__ . '/installed.php'; }
}
'''


def scan_classmap(classmaps):
    entries = {}
    rootcm = ROOT + "/"
    for name, c in classmaps:
        base = os.path.join(ROOT) if name == "" else os.path.join(VENDOR, *name.split("/"))
        target = os.path.join(base, c)
        targets = []
        if os.path.isdir(target):
            for dirpath, _, filenames in os.walk(target):
                for fn in filenames:
                    if fn.endswith(".php"):
                        targets.append(os.path.join(dirpath, fn))
        elif os.path.isfile(target):
            targets.append(target)
        for t in targets:
            try:
                src = open(t, encoding="utf-8", errors="ignore").read()
            except OSError:
                continue
            ns, cls = None, None
            for line in src.splitlines():
                ls = line.strip()
                if ns is None and ls.startswith("namespace "):
                    ns = ls[len("namespace "):].rstrip(";").strip()
                if cls is None and (ls.startswith("class ") or ls.startswith("final class ") or ls.startswith("abstract class ") or ls.startswith("interface ") or ls.startswith("trait ")):
                    parts = ls.split()
                    for w_i, w in enumerate(parts):
                        if w in ("class", "interface", "trait") or (w in ("final", "abstract") and parts[w_i + 1:w_i + 2] and parts[w_i + 1] in ("class",)):
                            cls = parts[w_i + 1]
                            break
                if cls:
                    break
            if cls:
                fqcn = (ns + "\\" + cls) if ns else cls
                if fqcn not in entries:
                    rel = os.path.relpath(t, ROOT)
                    entries[fqcn] = "$vendorDir . '/" + rel[len("vendor/"):] + "'" if rel.startswith("vendor/") else "__DIR__ . '/../" + rel + "'"
    return entries


def main():
    psr4, psr0, files, classmaps, installed = collect_autoload()
    cdir = os.path.join(VENDOR, "composer")
    os.makedirs(cdir, exist_ok=True)

    # ClassLoader + real
    open(os.path.join(cdir, "ClassLoader.php"), "w").write(CLASSLOADER)
    open(os.path.join(cdir, "autoload_real.php"), "w").write(AUTOLOAD_REAL)
    open(os.path.join(cdir, "InstalledVersions.php"), "w").write(INSTALLED_VERSIONS)

    # psr4
    lines = ["<?php", "", "// autoload_psr4.php @generated by vendor_install.py", "", "return ["]
    for ns, dirs in psr4.items():
        vals = ", ".join(php_str(d) for d in dirs)
        lines.append(f"    {php_str(ns)} => [{vals}],")
    lines.append("];")
    open(os.path.join(cdir, "autoload_psr4.php"), "w").write("\n".join(lines) + "\n")

    # psr-0 namespaces
    lines = ["<?php", "", "// autoload_namespaces.php @generated by vendor_install.py", "", "return ["]
    for ns, dirs in psr0.items():
        vals = ", ".join(php_str(d) for d in dirs)
        lines.append(f"    {php_str(ns)} => [{vals}],")
    lines.append("];")
    open(os.path.join(cdir, "autoload_namespaces.php"), "w").write("\n".join(lines) + "\n")

    # files (absolute VFS paths — the WASM runtime mounts the repo at /syst)
    entries = [php_str("/syst/" + f) for f in files]
    body = "<?php\n\n// autoload_files.php @generated by vendor_install.py\n\nreturn array(\n" + ",\n".join(entries) + ",\n);\n"
    open(os.path.join(cdir, "autoload_files.php"), "w").write(body)

    # classmap
    cm = scan_classmap(classmaps)
    lines = ["<?php", "", "// autoload_classmap.php @generated by vendor_install.py", "", "return ["]
    for fqcn, target in sorted(cm.items()):
        lines.append(f"    {php_str(fqcn)} => {target},")
    lines.append("];")
    open(os.path.join(cdir, "autoload_classmap.php"), "w").write("\n".join(lines) + "\n")

    # installed.json + installed.php
    root_pkg = pj(os.path.join(ROOT, "composer.json"))
    inst = {"packages": installed, "dev": bool(not PROD_ONLY), "dev-package-names": []}
    open(os.path.join(cdir, "installed.json"), "w").write(json.dumps(inst, indent=2))
    ver_php = ["<?php", "", "return array(", "    'root' => array(",
               "        'name' => " + php_str(root_pkg.get("name", "zsyst/app")) + ",",
               "        'version' => '1.0.0',",
               "        'pretty_version' => '1.0.0',",
               "        'reference' => null,",
               "    ),",
               "    'versions' => array("]
    for p in installed:
        ver_php.append(f"        {php_str(p['name'])} => array(")
        ver_php.append("            'version' => " + php_str(p.get("version") or "1.0.0") + ",")
        ver_php.append("            'pretty_version' => " + php_str(p.get("version") or "1.0.0") + ",")
        ver_php.append("            'reference' => " + (php_str(p.get("reference") or "") if p.get("reference") else "null") + ",")
        ver_php.append("        ),")
    ver_php += ["    ),", ");"]
    open(os.path.join(cdir, "installed.php"), "w").write("\n".join(ver_php) + "\n")

    # vendor/autoload.php
    open(os.path.join(VENDOR, "autoload.php"), "w").write(
        "<?php\n\n// autoload.php @generated by vendor_install.py\n\nrequire_once __DIR__ . '/composer/autoload_real.php';\n\nreturn ComposerAutoloaderInit::getLoader();\n")

    print(f"DONE: {len(installed)} packages installed, {len(files)} files-autoloads, {len(psr4)} psr-4 prefixes")


if __name__ == "__main__":
    main()
