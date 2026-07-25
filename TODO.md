# Update Controllers/Services to use StockAllocationService

## Progress Tracking

### Phase 1: Enhance StockAllocationService ✅
- [x] Already extended with ProductStock support
- [x] Added `addToProductStock()` method for creating/incrementing ProductStock records
- [x] Added `addToLegacyStock()` method for legacy Stock model

### Phase 2: Update Services ✅
- [x] **SaleService** - Replaced `deductStock()` FIFO with `StockAllocationService::allocateToProductStock()` - Removed legacy `deductStock()` method
- [x] **GoodsReceivedNoteService** - Replaced direct `ProductStock::create()` with `StockAllocationService::addToProductStock()` 
- [x] **PurchaseOrderReturnService** - Added stock deduction via `StockAllocationService::allocateToProductStock()`
- [x] **StockTransferService** - Replaced `deductStock()`/`addStock()` with `StockAllocationService` methods

### Phase 3: Update Controllers ✅
- [x] **Controllers/SaleController** - Replaced `Medicine::decrement('stock')` with `StockAllocationService::allocate()`
- [x] **Api/SaleReturnController** - Replaced `Stock::increment()` with `StockAllocationService::release()`
- [x] **Api/PurchaseReturnController** - Replaced `Stock::decrement()` with `StockAllocationService::allocate()`
- [x] **Api/PurchaseController** - Replaced direct Stock CRUD in store/update/destroy with `StockAllocationService` calls

### Phase 4: Integration Tests ✅
- [x] **tests/Feature/PurchaseReturnControllerTest.php** - 5 tests covering:
  - Creating purchase return with stock decrease via StockAllocationService::allocate()
  - Validation of required fields
  - Listing with date filtering
  - Showing with relations (purchase, party, details)
  - Error handling when stock insufficient
- [x] **tests/Feature/SaleReturnControllerTest.php** - 5 tests covering:
  - Creating sale return with stock increase via StockAllocationService::release()
  - Validation of required fields
  - Listing with date filtering
  - Showing with relations (sale, party, details)
  - Error handling when no stock record exists
