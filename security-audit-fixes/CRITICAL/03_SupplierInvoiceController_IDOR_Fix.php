<?php
/**
 * CRITICAL FIX: SupplierInvoiceController - IDOR Vulnerability
 * 
 * Issues:
 * - updateStatus() does not verify business_id
 * - getInvoiceNumbers() does not verify business_id
 */

// Add this use statement at the top
use App\Traits\HasBusinessScope;

class SupplierInvoiceController extends Controller
{
    use HasBusinessScope; // ADD THIS TRAIT
    
    // ... existing code ...
    
    /**
     * FIXED: updateStatus - Added business validation
     */
    public function updateStatus(Request $request, $id)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Verify invoice belongs to user's business
        $invoice = SupplierInvoice::forBusiness($businessId)
            ->findOrFail($id);
        
        // Now safe to update status
        $invoice->update([
            'status' => $request->status,
            // ... other fields ...
        ]);
        
        return response()->json($invoice);
    }
    
    /**
     * FIXED: getInvoiceNumbers - Added business validation
     */
    public function getInvoiceNumbers(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Use business scope
        $invoices = SupplierInvoice::forBusiness($businessId)
            ->select('id', 'invoice_number')
            ->get();
        
        return response()->json($invoices);
    }
}
