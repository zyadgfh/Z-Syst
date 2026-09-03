<?php

namespace App\Http\Controllers\Api;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartyRequest;
use App\Http\Requests\UpdatePartyRequest;
use App\Models\Business;
use App\Models\Party;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    use HasUploader;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Party::class);

        $data = Party::where('business_id', auth()->user()->business_id)->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePartyRequest $request)
    {
        $this->authorize('create', Party::class);

        // Validation is handled by StorePartyRequest

        $data = Party::create($request->validated() + [
            'opening_balance' => $request->due,
            'business_id' => auth()->user()->business_id,
            'image' => $request->image ? $this->upload($request, 'image') : null,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    public function show(Party $party)
    {
        $this->authorize('view', $party);

        if (config('zsyst.message_enabled')) {
            if ($party->due) {
                $business = Business::findOrFail($party->business_id);
                $response = sendMessage($party->phone, dueMessage($party, $business->companyName));

                if ($response->successful()) {
                    return response()->json([
                        'message' => __('Message has been send successfully.'),
                    ]);
                }

                return response()->json([
                    'message' => __('Something was wrong, Please contact with admin.'),
                ], 406);
            } else {
                return response()->json([
                    'message' => __('This party has no due balance.'),
                ], 406);
            }
        } else {
            return response()->json([
                'message' => __('Message has been disabled by admin.'),
            ], 406);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePartyRequest $request, Party $party)
    {
        $this->authorize('update', $party);

        // Validation is handled by UpdatePartyRequest

        $party->update($request->validated() + [
            'opening_balance' => $request->due,
            'image' => $request->image ? $this->upload($request, 'image', $party->image) : $party->image,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $party,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Party $party)
    {
        $this->authorize('delete', $party);

        $party->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
