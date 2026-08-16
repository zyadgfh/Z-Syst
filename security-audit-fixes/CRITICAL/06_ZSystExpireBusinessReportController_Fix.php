<?php
/**
 * CRITICAL FIX: ZSystExpireBusinessReportController - Business Scoping
 * 
 * Issues:
 * - getBusinesses() returns ALL businesses without scoping
 * - show() does not verify business_id
 */

// Add this use statement at the top
use App\Traits\HasBusinessScope;

class ZSystExpireBusinessReportController extends Controller
{
    use HasBusinessScope; // ADD THIS TRAIT
    
    // ... existing code ...
    
    /**
     * FIXED: getBusinesses - Added business scoping
     */
    public function getBusinesses(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Scope to user's business only
        $businesses = Business::where('id', $businessId)
            ->get();
        
        return response()->json($businesses);
    }
    
    /**
     * FIXED: show - Added business validation
     */
    public function show(Request $request, $id)
    {
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // FIX: Verify report belongs to user's business
        $report = ExpireBusinessReport::where('business_id', $businessId)
            ->findOrFail($id);
        
        return response()->json($report);
    }
}
