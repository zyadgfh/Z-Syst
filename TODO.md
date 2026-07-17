# Pharmacy UI + Forecasting

- [x] Update `resources/views/pharmacy-pos.blade.php` UI: skeleton loading, confidence badges, clearer fields, refresh summary button, improved dispense feedback.
- [x] Upgrade forecasting in `app/Http/Controllers/API/V1/PrescriptionController.php::demandForecast()` to use actual `dispensed_quantity` from prescription items, aggregate by product over the last 30 days, compute average + simple trend, and return richer fields.
- [x] Fix pharmacy forecasting to use `dispensed_quantity` for last 30 days (time basis + remove/limit stock-movement log influence if required).
- [x] Adjust POS summary mapping to new forecast fields (verify API shape vs UI expectation; update mapping if mismatch).
- [x] Update pharmacy-pos UI skeleton/confidence badges (improve placeholders/badges styling/labels if required).


## Smoke Test Checklist (Manual Testing)

### 1. Load Pharmacy POS Page
- [ ] Navigate to `/pharmacy-pos` - should load the page without errors
- [ ] Skeleton loading should appear while loading data
- [ ] Stats grid should show pending prescriptions, low stock alerts, and forecast count

### 2. API Endpoints Testing
- [ ] GET `/api/v1/pharmacy/pos-summary` - should return JSON with:
  - `pending_prescriptions_count`
  - `low_stock_products` array
  - `forecast` array with items containing:
    - `product_id`
    - `product_name`
    - `barcode`
    - `average_daily_demand`
    - `recommended_reorder_quantity`
    - `safety_stock`
    - `confidence` (high/medium/low)
    - `trend` (up/down/flat)

### 3. WhatsApp Invoice Sending
- [ ] Complete a sale to open receipt modal
- [ ] Enter customer phone number in the modal
- [ ] Click "Send via WhatsApp" button
- [ ] Verify POST to `/api/v1/send-invoice-whatsapp` works correctly

## Completed Bug Fixes

1. **InvoiceWhatsAppController** - Added missing `extends Controller` inheritance
2. **sale-print.blade.php** - Fixed API endpoint from `/api/send-invoice-whatsapp` to `/api/v1/send-invoice-whatsapp`
3. **VerifyCsrfToken.php** - Added `api/v1/send-invoice-whatsapp` to CSRF exceptions
4. **PrescriptionController.php** - Removed duplicate line in demandForecast method