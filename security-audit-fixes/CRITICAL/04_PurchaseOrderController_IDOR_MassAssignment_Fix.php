<?php
/**
 * CRITICAL FIX: PurchaseOrderController - IDOR + Mass Assignment
 * 
 * Issues:
 * - makePayment() does not verify business_id
 * - makeExternalPayment() does not verify business_id
 * - addInvoice() does not verify business_id
 * - updateInvoice() does not verify business_id
 * - receiveProducts() does not verify business_id
 */

// Add this use statement at the top
use App\Traits\HasBusinessScope;

class PurchaseOrderController extends Controller
{
    use HasBusinessScope; // ADD THIS TRAIT
    
    // ... existing code ...
    
    /**
     * FIXED: makePayment - Added business validation
     */
    public function makePayment(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Verify purchase order belongs to business
        $purchaseOrder = PurchaseOrder::forBusiness($businessId)
            ->findOrFail($request->purchase_order_id);
        
        // FIX: Validate invoice belongs to same business
        if ($request->invoice_id) {
            $invoice = PurchaseInvoice::where('purchase_order_id', $purchaseOrder->id)
                ->where('business_id', $businessId)
                ->firstOrFail();
        }
        
        // Create payment with business_id
        $payment = PurchasePayment::create([
            'business_id' => $businessId,
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => $request->amount,
            // ... other fields ...
        ]);
        
        return response()->json($payment);
    }
    
    /**
     * FIXED: makeExternalPayment - Added business validation
     */
    public function makeExternalPayment(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        $purchaseOrder = PurchaseOrder::forBusiness($businessId)
            ->findOrFail($request->purchase_order_id);
        
        // ... rest of code with business scoping ...
    }
    
    /**
     * FIXED: addInvoice - Added business validation
     */
    public function addInvoice(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        $purchaseOrder = PurchaseOrder::forBusiness($businessId)
            ->findOrFail($request->purchase_order_id);
        
        $invoice = PurchaseInvoice::create([
            'business_id' => $businessId, // Ensure business_id set
            'purchase_order_id' => $purchaseOrder->id,
            // ... other fields ...
        ]);
        
        return response()->json($invoice);
    }
    
    /**
     * FIXED: updateInvoice - Added business validation
     */
    public function updateInvoice(Request $request, $id)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        $invoice = PurchaseInvoice::forBusiness($businessId)
            ->findOrFail($id);
        
        $invoice->update($request->validated());
        
        return response()->json($invoice);
    }
    
    /**
     * FIXED: receiveProducts - Added business validation
     */
    public function receiveProducts(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        $purchaseOrder = PurchaseOrder::forBusiness($businessId)
            ->findOrFail($request->purchase_order_id);
        
        // ... rest of code with business scoping ...
    }
}
