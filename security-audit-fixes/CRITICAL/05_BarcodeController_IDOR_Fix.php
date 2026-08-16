<?php
/**
 * CRITICAL FIX: BarcodeController - IDOR + Missing Validation
 * 
 * Issues:
 * - makeLabel() does not verify business_id
 * - editLabel() does not verify business_id
 * - deleteLabel() does not verify business_id
 * - updateBarcodeSettings() does not verify business_id
 */

// Add this use statement at the top
use App\Traits\HasBusinessScope;

class BarcodeController extends Controller
{
    use HasBusinessScope; // ADD THIS TRAIT
    
    // ... existing code ...
    
    /**
     * FIXED: makeLabel - Added business validation
     */
    public function makeLabel(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Validate product belongs to business
        $product = Product::forBusiness($businessId)
            ->findOrFail($request->product_id);
        
        // FIX: Create label with business_id
        $label = BarcodeLabel::create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            // ... other fields ...
        ]);
        
        return response()->json($label);
    }
    
    /**
     * FIXED: editLabel - Added business validation
     */
    public function editLabel(Request $request, $id)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Verify label belongs to business
        $label = BarcodeLabel::forBusiness($businessId)
            ->findOrFail($id);
        
        $label->update($request->validated());
        
        return response()->json($label);
    }
    
    /**
     * FIXED: deleteLabel - Added business validation
     */
    public function deleteLabel(Request $request, $id)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Verify label belongs to business
        $label = BarcodeLabel::forBusiness($businessId)
            ->findOrFail($id);
        
        $label->delete();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * FIXED: updateBarcodeSettings - Added business validation
     */
    public function updateBarcodeSettings(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Find or create settings for this business
        $settings = BarcodeSetting::firstOrCreate(
            ['business_id' => $businessId],
            $request->validated()
        );
        
        if (!$settings->wasRecentlyCreated) {
            $settings->update($request->validated());
        }
        
        return response()->json($settings);
    }
}
