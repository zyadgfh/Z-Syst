<?php
/**
 * CRITICAL FIX: SubscriptionController - Missing Business Scoping
 * 
 * Issues:
 * - getSubscription() does not use forBusiness() scope
 * - addPlanToSubscription() does not verify business_id
 * - removePlanFromSubscription() does not verify business_id
 * - editSubscription() does not verify business_id
 * - renewSubscription() does not verify business_id
 * - cancelSubscription() does not verify business_id
 * - deleteSubscription() does not verify business_id
 */

// Add these use statements at the top of SubscriptionController.php
use App\Traits\HasBusinessScope;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends Controller
{
    use HasBusinessScope; // ADD THIS TRAIT
    
    // ... existing code ...
    
    /**
     * FIXED: getSubscription - Added business scoping
     */
    public function getSubscription(Request $request)
    {
        // FIX: Add business scoping
        $businessId = $request->user()->business_id;
        
        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }
        
        // Use forBusiness scope
        $subscription = Subscription::forBusiness($businessId)->first();
        
        if (!$subscription) {
            return response()->json(['error' => 'No subscription found'], 404);
        }
        
        return response()->json($subscription);
    }
    
    /**
     * FIXED: addPlanToSubscription - Added business validation
     */
    public function addPlanToSubscription(Request $request, $subscriptionId, $planId)
    {
        // FIX: Verify subscription belongs to user's business
        $businessId = $request->user()->business_id;
        
        $subscription = Subscription::forBusiness($businessId)
            ->findOrFail($subscriptionId);
        
        // ... rest of existing code ...
    }
    
    /**
     * FIXED: removePlanFromSubscription - Added business validation
     */
    public function removePlanFromSubscription(Request $request, $subscriptionId, $planId)
    {
        // FIX: Verify subscription belongs to user's business
        $businessId = $request->user()->business_id;
        
        $subscription = Subscription::forBusiness($businessId)
            ->findOrFail($subscriptionId);
        
        // ... rest of existing code ...
    }
    
    /**
     * FIXED: editSubscription - Added business validation
     */
    public function editSubscription(Request $request, $subscriptionId)
    {
        // FIX: Verify subscription belongs to user's business
        $businessId = $request->user()->business_id;
        
        $subscription = Subscription::forBusiness($businessId)
            ->findOrFail($subscriptionId);
        
        // ... rest of existing code ...
    }
    
    /**
     * FIXED: renewSubscription - Added business validation
     */
    public function renewSubscription(Request $request, $subscriptionId)
    {
        // FIX: Verify subscription belongs to user's business
        $businessId = $request->user()->business_id;
        
        $subscription = Subscription::forBusiness($businessId)
            ->findOrFail($subscriptionId);
        
        // ... rest of existing code ...
    }
    
    /**
     * FIXED: cancelSubscription - Added business validation
     */
    public function cancelSubscription(Request $request, $subscriptionId)
    {
        // FIX: Verify subscription belongs to user's business
        $businessId = $request->user()->business_id;
        
        $subscription = Subscription::forBusiness($businessId)
            ->findOrFail($subscriptionId);
        
        // ... rest of existing code ...
    }
    
    /**
     * FIXED: deleteSubscription - Added business validation
     */
    public function deleteSubscription(Request $request, $subscriptionId)
    {
        // FIX: Verify subscription belongs to user's business
        $businessId = $request->user()->business_id;
        
        $subscription = Subscription::forBusiness($businessId)
            ->findOrFail($subscriptionId);
        
        // ... rest of existing code ...
    }
}
