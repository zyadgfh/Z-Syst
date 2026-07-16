# Task TODO: Pharmacy UI + Forecasting

- [x] Update `resources/views/pharmacy-pos.blade.php` UI: skeleton loading, confidence badges, clearer fields, refresh summary button, improved dispense feedback.
- [x] Upgrade forecasting in `app/Http/Controllers/API/V1/PrescriptionController.php::demandForecast()` to use actual `dispensed_quantity` from prescription items, aggregate by product over the last 30 days, compute average + simple trend, and return richer fields.

- [x] Ensure `posSummary()` uses the upgraded forecast output (may need to adjust mapping).

- [x] Quick smoke test: load `/pharmacy-pos` and call `/api/v1/pharmacy/pos-summary` in browser/Postman.






