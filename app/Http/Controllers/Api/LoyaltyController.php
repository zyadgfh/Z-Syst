<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterInteractionsRequest;
use App\Http\Requests\StoreLoyaltyProgramRequest;
use App\Http\Requests\UpdateLoyaltyProgramRequest;
use App\Http\Resources\CustomerInteractionResource;
use App\Http\Resources\LoyaltyProgramResource;
use App\Http\Resources\LoyaltyTransactionResource;
use App\Models\CustomerInteraction;
use App\Models\LoyaltyProgram;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function index(Request $request)
    {
        $programs = LoyaltyProgram::where('business_id', $request->user()->business_id)
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => LoyaltyProgramResource::collection($programs),
        ]);
    }

    public function store(StoreLoyaltyProgramRequest $request)
    {
        $validated = $request->validated();

        $validated['business_id'] = $request->user()->business_id;

        $program = LoyaltyProgram::create($validated);

        return response()->json([
            'message' => __('Loyalty program created successfully.'),
            'data' => new LoyaltyProgramResource($program),
        ], 201);
    }

    public function update(UpdateLoyaltyProgramRequest $request, LoyaltyProgram $program)
    {
        $this->authorizeProgramAccess($request, $program);

        $validated = $request->validated();

        $program->update($validated);

        return response()->json([
            'message' => __('Loyalty program updated successfully.'),
            'data' => new LoyaltyProgramResource($program),
        ]);
    }

    public function destroy(Request $request, LoyaltyProgram $program)
    {
        $this->authorizeProgramAccess($request, $program);

        $program->delete();

        return response()->json([
            'message' => __('Loyalty program deleted successfully.'),
        ]);
    }

    public function partyBalance(Request $request, $partyId = null)
    {
        $partyId = $partyId ?? $request->party_id;

        if (! $partyId) {
            return response()->json(['message' => __('Party ID is required.')], 422);
        }

        $balance = $this->loyaltyService->getBalance($request->user()->business_id, $partyId);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => [
                'party_id' => (int) $partyId,
                'balance' => (int) $balance,
            ],
        ]);
    }

    public function partyHistory(Request $request, $partyId = null)
    {
        $partyId = $partyId ?? $request->party_id;

        if (! $partyId) {
            return response()->json(['message' => __('Party ID is required.')], 422);
        }

        $history = $this->loyaltyService->getHistory($request->user()->business_id, $partyId);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => LoyaltyTransactionResource::collection($history),
        ]);
    }

    public function interactions(FilterInteractionsRequest $request)
    {
        $validated = $request->validated();

        $query = CustomerInteraction::where('business_id', $request->user()->business_id);

        if (isset($validated['party_id'])) {
            $query->where('party_id', $validated['party_id']);
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $interactions = $query->with(['party', 'user'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => CustomerInteractionResource::collection($interactions),
        ]);
    }

    protected function authorizeProgramAccess(Request $request, LoyaltyProgram $program): void
    {
        if ($program->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }
    }
}
