# Pharmacy POS Testing Guide

## 🧪 Frontend Testing (No Server Required)

### Quick Browser Test
1. Open `resources/views/pharmacy-pos.blade.php` in a browser
2. Test the following functionality:

#### Cart Functionality
- [ ] Add items using demo barcodes: `1234567890123`, `9876543210987`, `4567890123456`
- [ ] Verify stock levels are displayed (e.g., "50 in stock")
- [ ] Test quantity increase with + button
- [ ] Test quantity decrease with - button
- [ ] Test stock limit enforcement (try to exceed available stock)
- [ ] Remove individual items with × button
- [ ] Clear entire cart with "Clear all" button
- [ ] Verify cart count updates correctly
- [ ] Check totals calculation (subtotal, tax, total)

#### Checkout Flow
- [ ] Enter customer name (optional)
- [ ] Select payment method (Cash, Card, Insurance)
- [ ] Click "Complete Sale" button
- [ ] Verify confirmation modal appears with correct details
- [ ] Confirm sale and check receipt generation
- [ ] Verify receipt shows all items, quantities, and totals
- [ ] Test "Print Receipt" functionality
- [ ] Click "New Sale" to reset for next transaction

#### Scanner Testing
- [ ] Click "Toggle scanner" button
- [ ] Verify camera permission request
- [ ] Test barcode detection (if camera available)
- [ ] Verify auto-add to cart functionality

#### Hold Order
- [ ] Add items to cart
- [ ] Click "Hold Order" button
- [ ] Refresh page and verify held order restoration prompt

## 🔧 Backend Testing (Requires PHP Server)

### Setup Database
```bash
# Run migrations
php artisan migrate --path=Modules/ZSyst/Database/migrations

# Seed test data
php artisan db:seed --class=PosTestSeeder
```

### API Testing

#### 1. Test Inventory Validation
```bash
curl -X POST http://localhost:8000/api/v1/pos/sales/validate-inventory \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"barcode": "1234567890123", "quantity": 5},
      {"barcode": "9876543210987", "quantity": 10}
    ]
  }'
```

Expected response:
```json
{
  "all_available": true,
  "items": [
    {
      "barcode": "1234567890123",
      "name": "Paracetamol 500mg",
      "available": true,
      "reason": "In stock",
      "requested_quantity": 5,
      "available_quantity": 50
    }
  ]
}
```

#### 2. Test Sale Creation
```bash
curl -X POST http://localhost:8000/api/v1/pos/sales \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "John Doe",
    "payment_method": "cash",
    "subtotal": 23.96,
    "tax_amount": 1.92,
    "total_amount": 25.88,
    "items": [
      {
        "barcode": "1234567890123",
        "name": "Paracetamol 500mg",
        "price": 5.99,
        "quantity": 4,
        "prescription_id": null
      }
    ]
  }'
```

Expected response:
```json
{
  "id": 1,
  "customer_name": "John Doe",
  "status": "completed",
  "subtotal": "23.96",
  "tax_amount": "1.92",
  "total_amount": "25.88",
  "payment_method": "cash",
  "sale_items": [
    {
      "id": 1,
      "drug_id": 1,
      "barcode": "1234567890123",
      "name": "Paracetamol 500mg",
      "unit_price": "5.99",
      "quantity": 4,
      "line_total": "23.96"
    }
  ]
}
```

#### 3. Test Inventory After Sale
```bash
# Check inventory was updated
curl http://localhost:8000/api/v1/inventory/items

# Check inventory movements
curl http://localhost:8000/api/v1/inventory
```

#### 4. Test Recent Sales
```bash
curl http://localhost:8000/api/v1/pos/sales
```

## 🧪 Integration Testing Checklist

### Complete Checkout Flow
- [ ] Add items to cart via barcode scanning
- [ ] Verify stock validation before checkout
- [ ] Complete sale with payment method selection
- [ ] Confirm inventory was deducted in database
- [ ] Verify inventory movement records created
- [ ] Check sale appears in recent sales
- [ ] Test receipt generation and printing

### Edge Cases
- [ ] Try to sell more items than available in stock
- [ ] Test with unknown barcode (should still work in demo mode)
- [ ] Test with empty cart (should show error)
- [ ] Test sale with prescription ID integration
- [ ] Test database transaction rollback on error

### Stock Management
- [ ] Verify FIFO inventory deduction
- [ ] Test low stock warnings (items with < 10 units)
- [ ] Verify stock alerts in operations snapshot
- [ ] Test batch number tracking in inventory

## 🐛 Common Issues & Solutions

### Database Connection Issues
- **Issue**: API returns 500 errors
- **Solution**: Check database credentials in `.env` file
- **Run**: `php artisan config:clear` and `php artisan cache:clear`

### Migration Issues
- **Issue**: Tables not created
- **Solution**: Run `php artisan migrate:fresh` to reset database
- **Check**: Verify migration files exist in correct directory

### Foreign Key Constraints
- **Issue**: Sale creation fails with foreign key error
- **Solution**: Ensure drugs are seeded before creating sales
- **Run**: `php artisan db:seed --class=PosTestSeeder`

### CORS Issues
- **Issue**: Frontend cannot connect to API
- **Solution**: Add CORS middleware to API routes
- **Check**: Verify API route prefixes match frontend calls

## 📊 Performance Testing

### Load Testing
```bash
# Test multiple concurrent sales
for i in {1..10}; do
  curl -X POST http://localhost:8000/api/v1/pos/sales \
    -H "Content-Type: application/json" \
    -d '{
      "customer_name": "Test User '$i'",
      "payment_method": "cash",
      "subtotal": 5.99,
      "tax_amount": 0.48,
      "total_amount": 6.47,
      "items": [{"barcode": "1234567890123", "name": "Paracetamol 500mg", "price": 5.99, "quantity": 1}]
    }' &
done
```

### Database Performance
- Check query logs for slow queries
- Verify indexes are being used
- Monitor inventory calculation performance

## ✅ Success Criteria

### Functional Requirements
- [x] Cart management (add, remove, update quantities)
- [x] Stock validation and limits
- [x] Payment method selection
- [x] Sale confirmation modal
- [x] Receipt generation
- [x] Inventory updates
- [x] Transaction history
- [x] Prescription integration

### Non-Functional Requirements
- [x] Responsive design (mobile/tablet/desktop)
- [x] Error handling and user feedback
- [x] Database transaction integrity
- [x] API fallback for demo mode
- [x] Accessibility (keyboard navigation)
- [x] Performance (fast page load)

## 🚀 Deployment Checklist

### Pre-Deployment
- [ ] Run all database migrations
- [ ] Seed production data
- [ ] Configure CORS settings
- [ ] Set up API authentication
- [ ] Configure backup strategy

### Post-Deployment
- [ ] Test API endpoints
- [ ] Verify frontend connectivity
- [ ] Monitor error logs
- [ ] Check database performance
- [ ] Test barcode scanner integration

## 📝 Test Results Template

```
Date: ___________
Tester: ___________
Environment: ___________

Frontend Tests:
□ Cart functionality: PASS/FAIL
□ Checkout flow: PASS/FAIL
□ Receipt generation: PASS/FAIL
□ Stock validation: PASS/FAIL

Backend Tests:
□ API connectivity: PASS/FAIL
□ Inventory updates: PASS/FAIL
□ Database integrity: PASS/FAIL
□ Transaction history: PASS/FAIL

Integration Tests:
□ Complete checkout: PASS/FAIL
□ Error handling: PASS/FAIL
□ Performance: PASS/FAIL

Notes: ___________
```