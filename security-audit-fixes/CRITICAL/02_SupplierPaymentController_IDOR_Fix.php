<?php
/**
 * CRITICAL FIX: SupplierPaymentController - IDOR Vulnerability
 * 
 * Issues:
 * - makePayment() does not verify business_id
 * - makeExternalPayment() does not verify business_id
 * - makeSupplierPaymentFromAccount() does not verify business_id
 */

// Add this use statement at the top
use App\Traits\HasBusinessScope;

class SupplierPaymentController extends Controller
{
    use HasBusinessScope; // ADD THIS TRAIT
    
    // ... existing code ...
    
    /**
     * FIXED: makePayment - Added business validation
     */
    public function makePayment(Request $request)
    {
        // FIX: Validate business_id
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Validate supplier belongs to this business
        $supplier = Supplier::forBusiness($businessId)
            ->findOrFail($request->supplier_id);
        
        // FIX: Validate invoice belongs to this business
        if ($request->invoice_id) {
            $invoice = SupplierInvoice::forBusiness($businessId)
                ->findOrFail($request->invoice_id);
        }
        
        // ... rest of existing code, using $businessId throughout ...
        
        // FIX: Use business scoping when creating payment
        $payment = SupplierPayment::create([
            'business_id' => $businessId, // Ensure business_id is set
            'supplier_id' => $supplier->id,
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
        
        // Validate supplier belongs to business
        $supplier = Supplier::forBusiness($businessId)
            ->findOrFail($request->supplier_id);
        
        // ... rest of code with business scoping ...
    }
    
    /**
     * FIXED: makeSupplierPaymentFromAccount - Added business validation
     */
    public function makeSupplierPaymentFromAccount(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // Validate supplier belongs to business
        $supplier = Supplier::forBusiness($businessId)
            ->findOrFail($request->supplier_id);
        
        // Validate account belongs to business
        $account = Account::forBusiness($businessId)
            ->findOrFail($request->account_id);
        
        // ... rest of code with business scoping ...
    }
}
