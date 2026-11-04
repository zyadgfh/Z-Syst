# 17 — Live infra closure via connector (2026-11-01)

Follow-up to `16-ci-restore-and-hardening.md`. Everything here was executed **live** through the
Supabase connector against `atvttthrqsybjjwrbiyl` — no runtime keys, no deploys, no git ops.

## 1. Realtime for the POS (KB roadmap P1-4 — closed)

```sql
ALTER PUBLICATION supabase_realtime ADD TABLE
  public.sales, public.sale_details, public.sale_items,
  public.products, public.stocks, public.goods_received_notes;
```

Verified: publication now carries exactly these 6 tables (was 0). All six are RLS-protected with
`business_id`-scoped policies, so realtime streams stay tenant-isolated — no policy changes needed.

Flutter side (when wiring live updates): subscribe per-table with the user's GoTrue JWT;
`RealtimeClient` re-checks RLS per subscriber.

## 2. Storage foundation (KB roadmap P1-5 — foundation closed)

- Bucket `uploads`: **private**, `file_size_limit = 5 242 880` (5 MB),
  `allowed_mime_types = {image/png, image/jpeg, image/webp, image/svg+xml, application/pdf}`.
- Four policies on `storage.objects` for `authenticated`, folder-scoped:

```sql
-- layout: uploads/{business_id}/<anything>
USING/WITH CHECK (bucket_id = 'uploads'
  AND (storage.foldername(name))[1] = private.current_business_id()::text)
-- zsyst_tenant_read_uploads (SELECT)
-- zsyst_tenant_insert_uploads (INSERT WITH CHECK)
-- zsyst_tenant_update_uploads (USING + WITH CHECK)
-- zsyst_tenant_delete_uploads (USING)
```

No `anon` policies — bucket is invisible to unauthenticated clients.
Remaining (needs a booted runtime): Laravel filesystem disk `supabase` pointing at the bucket,
one-off migration of the 109 tracked files in `public/uploads`, then `git rm -r --cached public/uploads`.

## 3. Password policy — definitively connector-blocked

Attempted `update_auth_config` with `password_min_length=8` + `password_hibp_enabled=true`
(all other settable keys re-sent defensively). Live schema rejection enumerates the allowed keys:
`disable_signup, external_email_enabled, external_phone_enabled, jwt_exp, mailer_autoconfirm,
security_refresh_token_reuse_interval, security_refresh_token_rotation_enabled, site_url,
sms_autoconfirm, uri_allow_list`. **No settings were modified** (call rejected atomically).

⇒ `password_min_length` (currently 6) and HIBP stay **dashboard/`sbp_`-only** — unchanged from §5c.

## 4. Post-change verification

- `zsyst_qa.rls_audit()` → **7/7 PASSED** (95 tables · 0 without RLS · 0 client tables without
  policies · 0 anon policies · INSERT policies scoped · self-edit guard present · 84/84 scoped).
- Services all `ACTIVE_HEALTHY` (checked 2026-11-01 via `get_project_health`).
