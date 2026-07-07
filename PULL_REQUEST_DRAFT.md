Title: Add landing/docs pages, tests, CI, and stock transfer auth fixes

Summary:
Implements public marketing and documentation pages (`landing`, `features`, `docs`), adds feature and unit tests, introduces a GitHub Actions workflow for PHPUnit, and fixes stock transfer authorization and notifications migration.

Changes included (high level):
- Public pages and Blade layouts (`resources/views/landing.blade.php`, `features.blade.php`, `docs.blade.php`, layouts)
- Docs markdown under `docs/` including `docs/pharmasync-feature-list.md`
- Stock transfer domain: models, controllers, requests, services, policy, listeners, notifications, factories, and tests
- Analytics and reporting services
- CI workflow: `.github/workflows/phpunit.yml`
- Added `.gitignore` and removed tracked runtime artifacts

Test results (local):
- `php artisan test` => 54 passed, 226 assertions (duration ~6s)

How to create the PR (choose one):

1) Using `gh` (after `gh auth login`):

```bash
gh pr create --base main --head feature/landing-docs-ci \
  --title "Add landing/docs pages, tests, CI, and stock transfer auth fixes" \
  --body "Implements public marketing and docs pages, adds feature tests and CI workflow, and fixes stock transfer authorization and notifications migration. Tests: 54 passed locally."
```

2) Open in browser (prefilled):

https://github.com/zyadga/Z-Syst/pull/new/feature/landing-docs-ci?title=Add%20landing/docs%20pages,%20tests,%20CI,%20and%20stock%20transfer%20auth%20fixes&body=Implements%20public%20marketing%20and%20docs%20pages,%20adds%20feature%20tests%20and%20CI%20workflow,%20and%20fixes%20stock%20transfer%20authorization%20and%20notifications%20migration.%20Tests%3A%2054%20passed%20locally.

Notes and checklist for PR reviewer:
- Verify doc pages render in staging
- Confirm CI runs and passes on PR
- Check stock transfer policy behavior with `super-admin` and normal users
- Ensure migrations `2026_07_07_*` are safe to run in target environment

If you want, I can try to run `gh pr create` now — but your `gh` session was previously unauthenticated. You can either run `gh auth login` locally, or I can open the browser PR link for you.