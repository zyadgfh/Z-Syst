#!/usr/bin/env python3
"""
upload_to_supabase.py — migrate local user uploads to the Supabase Storage
`uploads` bucket, preserving the relative path structure (e.g. 25/01/x.webp).

Bucket facts (verified 2026-11-02 via storage.buckets):
  - private, 5 MB limit
  - allowed mimes: image/png, image/jpeg, image/webp, image/svg+xml, application/pdf

Credentials (choose one):
  - env:  SUPABASE_URL + SUPABASE_SERVICE_ROLE_KEY
  - .env: same names, parsed from the repo .env (SUPABASE_URL may also be
    derived from the project ref)

Usage:
  python3 tools_local/upload_to_supabase.py [--root public/uploads] [--dry-run]

Idempotent: uses x-upsert, so re-running is safe.
"""
import argparse
import mimetypes
import os
import sys
import urllib.request
from pathlib import Path

REPO = Path(__file__).resolve().parent.parent
DEFAULT_ROOT = REPO / "public" / "uploads"
PROJECT_REF = "atvttthrqsybjjwrbiyl"  # Z-Syst Supabase project
BUCKET = "uploads"


def load_env(path: Path) -> dict:
    env = {}
    if path.exists():
        for line in path.read_text().splitlines():
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                k, _, v = line.partition("=")
                env[k.strip()] = v.strip().strip('"')
    return env


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--root", default=str(DEFAULT_ROOT))
    ap.add_argument("--dry-run", action="store_true")
    args = ap.parse_args()

    env = {**load_env(REPO / ".env"), **os.environ}
    url = env.get("SUPABASE_URL") or f"https://{PROJECT_REF}.supabase.co"
    root = Path(args.root)
    files = sorted(p for p in root.rglob("*") if p.is_file())
    print(f"Found {len(files)} files under {root}")

    if args.dry_run:
        for p in files[:10]:
            print("  would upload:", p.relative_to(root))
        print(f"... and {max(0, len(files) - 10)} more")
        return 0

    key = env.get("SUPABASE_SERVICE_ROLE_KEY")
    if not key:
        print("ERROR: SUPABASE_SERVICE_ROLE_KEY missing (env or .env).", file=sys.stderr)
        return 2

        for p in files[:10]:
            print("  would upload:", p.relative_to(root))
        print(f"... and {max(0, len(files) - 10)} more")
        return 0

    endpoint = f"{url}/storage/v1/object/{BUCKET}/"
    ok = fail = 0
    failures = []
    for i, p in enumerate(files, 1):
        rel = p.relative_to(root).as_posix()
        mime = mimetypes.guess_type(p.name)[0] or "application/octet-stream"
        data = p.read_bytes()
        req = urllib.request.Request(
            endpoint + rel,
            data=data,
            method="POST",
            headers={
                "Authorization": f"Bearer {key}",
                "Content-Type": mime,
                "x-upsert": "true",
                "cache-control": "31536000",
            },
        )
        try:
            with urllib.request.urlopen(req, timeout=60) as resp:
                resp.read()
            ok += 1
        except Exception as exc:  # noqa: BLE001
            fail += 1
            failures.append((rel, str(exc)))
        if i % 20 == 0 or i == len(files):
            print(f"  {i}/{len(files)} (ok={ok} fail={fail})")

    if failures:
        print("\nFailures:")
        for rel, err in failures[:20]:
            print("  ", rel, "->", err)
    print(f"\nDONE: {ok} uploaded, {fail} failed")
    return 1 if fail else 0


if __name__ == "__main__":
    sys.exit(main())
