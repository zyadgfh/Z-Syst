# 19 — uploads/ → Supabase Storage migration

Status: **READY — one command, owner-run** (this sandbox's egress is limited to
github/npm/pypi, so `*.supabase.co` is unreachable from here; everything else
is prepared and verified).

## What moves
- `public/uploads/**` — **109 files / 19 MB** (103 svg, 4 jpg, 1 png, 1 webp;
  max size 1.36 MB).
- Layout preserved: `YY/MM/filename` → bucket object path `25/01/…` etc.

## Target bucket (verified live 2026-11-02 via storage.buckets)
- `uploads` — **private**, 5 MB limit
- allowed mimes: image/png, image/jpeg, image/webp, image/svg+xml, application/pdf
- every local file matches the mime allow-list and size cap ✓

## Run it (from any machine with normal internet)
```bash
export SUPABASE_URL=https://atvttthrqsybjjwrbiyl.supabase.co
export SUPABASE_SERVICE_ROLE_KEY=<service_role key>   # Supabase → Settings → API
python3 tools_local/upload_to_supabase.py             # add --dry-run to preview
```
- Uses `x-upsert: true` → safe to re-run.
- Progress prints every 20 files; exits non-zero listing any failures.

## Verify afterwards
```bash
# count objects per folder (Storage API, needs the same key)
curl -s "$SUPABASE_URL/storage/v1/object/list/uploads" \
  -H "Authorization: Bearer $SUPABASE_SERVICE_ROLE_KEY" \
  -H "Content-Type: application/json" -d '{"prefix":"25","limit":100}' | head
```
Expect 109 objects total (103 under `25/02`, 5 under `25/01`, 1 under `25/03` —
match with `find public/uploads -type f | cut -d/ -f3 | sort | uniq -c`).

## App-side notes
- The app keeps serving previously-DB-referenced paths; switch render code to
  signed/public URLs only when the storage disk config lands (config/supabase.php
  already carries url+key envs).
- Keep `public/uploads/` out of deploys afterwards (already gitignored, line 44).
