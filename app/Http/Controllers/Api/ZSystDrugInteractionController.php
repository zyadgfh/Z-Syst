<?php

namespace App\Http\Controllers\Api;

use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Models\DrugInteraction;
use App\Models\Product;
use Illuminate\Http\Request;

class ZSystDrugInteractionController extends Controller
{
    /**
     * Display a listing of drug interactions (paginated).
     */
    public function index(Request $request)
    {
        $data = DrugInteraction::query()
            ->forBusiness(auth()->user()->business_id)
            ->when($request->input('search'), function ($query) use ($request) {
                $query->search($request->input('search'));
            })
            ->when($request->input('severity'), function ($query) use ($request) {
                $query->severity($request->input('severity'));
            })
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created drug interaction.
     */
    public function store(Request $request)
    {
        $request->validate([
            'drug_a_name' => 'required|string|max:255',
            'drug_b_name' => 'required|string|max:255|different:drug_a_name',
            'severity' => 'required|in:contraindicated,severe,moderate,minor',
            'description' => 'required|string|max:5000',
            'mechanism' => 'nullable|string|max:5000',
            'recommendation' => 'nullable|string|max:5000',
            'source' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
        ]);

        // Normalize: Ensure alphabetical order to prevent duplicate reverse entries
        $drugs = [$request->drug_a_name, $request->drug_b_name];
        sort($drugs);

        $interaction = TransactionHelper::run(function () use ($request, $drugs) {
            return DrugInteraction::create([
                'business_id' => auth()->user()->business_id,
                'drug_a_name' => $drugs[0],
                'drug_b_name' => $drugs[1],
                'severity' => $request->severity,
                'description' => $request->description,
                'mechanism' => $request->mechanism,
                'recommendation' => $request->recommendation,
                'source' => $request->source,
                'category' => $request->category,
                'meta' => [
                    'created_by' => auth()->id(),
                    'created_at' => now()->toDateTimeString(),
                ],
            ]);
        }, 'drug-interaction:store', [
            'drug_a_name' => $drugs[0],
            'drug_b_name' => $drugs[1],
        ]);

        return response()->json([
            'message' => __('Drug interaction saved successfully.'),
            'data' => $interaction,
        ]);
    }

    /**
     * Display the specified drug interaction.
     */
    public function show($id)
    {
        $interaction = DrugInteraction::findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $interaction,
        ]);
    }

    /**
     * Update the specified drug interaction.
     */
    public function update(Request $request, $id)
    {
        $interaction = DrugInteraction::findOrFail($id);

        $request->validate([
            'drug_a_name' => 'required|string|max:255',
            'drug_b_name' => 'required|string|max:255|different:drug_a_name',
            'severity' => 'required|in:contraindicated,severe,moderate,minor',
            'description' => 'required|string|max:5000',
            'mechanism' => 'nullable|string|max:5000',
            'recommendation' => 'nullable|string|max:5000',
            'source' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
        ]);

        $drugs = [$request->drug_a_name, $request->drug_b_name];
        sort($drugs);

        $interaction = TransactionHelper::run(function () use ($request, $interaction, $drugs) {
            $interaction->update([
                'drug_a_name' => $drugs[0],
                'drug_b_name' => $drugs[1],
                'severity' => $request->severity,
                'description' => $request->description,
                'mechanism' => $request->mechanism,
                'recommendation' => $request->recommendation,
                'source' => $request->source,
                'category' => $request->category,
            ]);

            return $interaction->fresh();
        }, 'drug-interaction:update', ['interaction_id' => $id]);

        return response()->json([
            'message' => __('Drug interaction updated successfully.'),
            'data' => $interaction,
        ]);
    }

    /**
     * Remove the specified drug interaction.
     */
    public function destroy($id)
    {
        $interaction = DrugInteraction::findOrFail($id);
        $interaction->delete();

        return response()->json([
            'message' => __('Drug interaction deleted successfully.'),
        ]);
    }

    /**
     * Check for interactions between a list of product IDs.
     *
     * Matches by generic_name (from product meta), then falls back to productName.
     */
    public function check(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array|min:2',
            'product_ids.*' => 'required|integer|exists:products,id',
        ]);

        $products = Product::whereIn('id', $request->product_ids)
            ->where('business_id', auth()->user()->business_id)
            ->get();

        if ($products->count() < 2) {
            return response()->json([
                'message' => __('At least 2 products are required for interaction check.'),
                'data' => [],
            ]);
        }

        // Extract drug names from products (generic_name from meta, then productName)
        $drugNames = $products->map(function ($product) {
            $meta = $product->meta;
            $genericName = $meta['generic_name'] ?? null;

            return [
                'product_id' => $product->id,
                'product_name' => $product->productName,
                'generic_name' => $genericName,
                'search_name' => $genericName ?? $product->productName,
            ];
        });

        // Collect all unique drug names to search
        $allSearchNames = $drugNames->pluck('search_name')->unique()->values()->toArray();

        // Find interactions where drug_a and drug_b are in the list
        $interactions = DrugInteraction::forBusiness(auth()->user()->business_id)
            ->where(function ($query) use ($allSearchNames) {
                foreach ($allSearchNames as $name) {
                    $query->orWhere(function ($q) use ($name, $allSearchNames) {
                        $q->where('drug_a_name', $name)
                            ->whereIn('drug_b_name', $allSearchNames);
                    });
                }
            })
            ->get();

        // Format results with product info
        $results = [];
        foreach ($interactions as $interaction) {
            $drugAProduct = $drugNames->firstWhere('search_name', $interaction->drug_a_name);
            $drugBProduct = $drugNames->firstWhere('search_name', $interaction->drug_b_name);

            if ($drugAProduct && $drugBProduct) {
                $results[] = [
                    'id' => $interaction->id,
                    'severity' => $interaction->severity,
                    'severity_label' => DrugInteraction::SEVERITY_LABELS[$interaction->severity] ?? $interaction->severity,
                    'severity_color' => DrugInteraction::SEVERITY_COLORS[$interaction->severity] ?? '#6B7280',
                    'description' => $interaction->description,
                    'mechanism' => $interaction->mechanism,
                    'recommendation' => $interaction->recommendation,
                    'source' => $interaction->source,
                    'category' => $interaction->category,
                    'drug_a' => [
                        'product_id' => $drugAProduct['product_id'],
                        'product_name' => $drugAProduct['product_name'],
                        'generic_name' => $drugAProduct['generic_name'],
                        'matched_name' => $interaction->drug_a_name,
                    ],
                    'drug_b' => [
                        'product_id' => $drugBProduct['product_id'],
                        'product_name' => $drugBProduct['product_name'],
                        'generic_name' => $drugBProduct['generic_name'],
                        'matched_name' => $interaction->drug_b_name,
                    ],
                ];
            }
        }

        // Group results by severity for UI convenience
        $grouped = collect($results)->groupBy('severity');

        return response()->json([
            'message' => count($results) > 0
                ? __('Found :count potential drug interaction(s).', ['count' => count($results)])
                : __('No interactions found between the selected products.'),
            'data' => [
                'checked_products' => $drugNames,
                'interactions' => $results,
                'total_interactions' => count($results),
                'has_critical' => $grouped->has('contraindicated') || $grouped->has('severe'),
                'grouped' => [
                    'contraindicated' => $grouped->get('contraindicated', collect()),
                    'severe' => $grouped->get('severe', collect()),
                    'moderate' => $grouped->get('moderate', collect()),
                    'minor' => $grouped->get('minor', collect()),
                ],
            ],
        ]);
    }

    /**
     * Bulk import drug interactions (for initial data loading by user).
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'interactions' => 'required|array|min:1',
            'interactions.*.drug_a_name' => 'required|string|max:255',
            'interactions.*.drug_b_name' => 'required|string|max:255|different:drug_a_name',
            'interactions.*.severity' => 'required|in:contraindicated,severe,moderate,minor',
            'interactions.*.description' => 'required|string|max:5000',
            'interactions.*.mechanism' => 'nullable|string|max:5000',
            'interactions.*.recommendation' => 'nullable|string|max:5000',
            'interactions.*.source' => 'nullable|string|max:500',
            'interactions.*.category' => 'nullable|string|max:100',
        ]);

        $businessId = auth()->user()->business_id;
        $imported = 0;
        $skipped = 0;

        foreach ($request->interactions as $data) {
            $drugs = [$data['drug_a_name'], $data['drug_b_name']];
            sort($drugs);

            try {
                DrugInteraction::create([
                    'business_id' => $businessId,
                    'drug_a_name' => $drugs[0],
                    'drug_b_name' => $drugs[1],
                    'severity' => $data['severity'],
                    'description' => $data['description'],
                    'mechanism' => $data['mechanism'] ?? null,
                    'recommendation' => $data['recommendation'] ?? null,
                    'source' => $data['source'] ?? null,
                    'category' => $data['category'] ?? null,
                    'meta' => [
                        'imported_by' => auth()->id(),
                        'imported_at' => now()->toDateTimeString(),
                    ],
                ]);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        return response()->json([
            'message' => __('Imported :imported interactions, :skipped skipped (duplicates).', [
                'imported' => $imported,
                'skipped' => $skipped,
            ]),
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => count($request->interactions),
            ],
        ]);
    }
}
