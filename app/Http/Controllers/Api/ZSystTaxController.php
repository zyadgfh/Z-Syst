<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;

class ZSystTaxController extends Controller
{
    public function index(Request $request)
    {
        $taxes = Tax::where('business_id', auth()->user()->business_id)
            ->when($request->input('type') == 'single', function ($query) {
                $query->whereNull('sub_tax');
            })
            ->when($request->input('type') == 'group', function ($query) {
                $query->whereNotNull('sub_tax');
            })
            ->when($request->input('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status') == 'active' ? 1 : 0);
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data fetched successfully.',
            'data' => $taxes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tax_ids' => 'nullable|array|min:1',
            'tax_ids.*' => 'integer|exists:taxes,id',
            'rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($request->rate && ! $request->tax_ids) {

            $tax = Tax::create([
                'rate' => $request->rate,
                'sub_tax' => null,
                'name' => $request->name,
                'status' => $request->input('status', 1),
                'business_id' => auth()->user()->business_id,
            ]);

        } elseif (! $request->rate && $request->tax_ids) {

            $taxs = Tax::where('business_id', auth()->user()->business_id)
                ->whereIn('id', $request->tax_ids)
                ->select('id', 'name', 'rate')
                ->get();

            if ($taxs->count() !== count($request->tax_ids)) {
                abort(403, 'One or more tax references do not belong to the current tenant.');
            }

            $tax_rate = 0;
            $sub_taxes = [];

            foreach ($taxs as $tax) {
                $sub_taxes[] = [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'rate' => $tax->rate,
                ];
                $tax_rate += $tax->rate;
            }

            $tax = Tax::create([
                'rate' => $tax_rate,
                'sub_tax' => $sub_taxes,
                'name' => $request->name,
                'business_id' => auth()->user()->business_id,
            ]);

        } else {
            return response()->json([
                'message' => 'Invalid data format.',
            ], 406);
        }

        return response()->json([
            'message' => 'Data created successfully.',
            'data' => $tax,
        ]);
    }

    public function update(Request $request, Tax $tax)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tax_ids' => 'required_if:rate,null',
            'rate' => 'required_if:rate,null|numeric',
        ]);

        if ($request->rate && ! $request->tax_ids) {

            $tax->update([
                'rate' => $request->rate,
                'name' => $request->name,
                'status' => $request->input('status', $tax->status),
                'sub_tax' => null,
            ]);

        } elseif (! $request->rate && $request->tax_ids) {

            $taxes = Tax::where('business_id', auth()->user()->business_id)
                ->whereIn('id', $request->tax_ids)
                ->select('id', 'name', 'rate')
                ->get();

            if ($taxes->count() !== count($request->tax_ids)) {
                abort(403, 'One or more tax references do not belong to the current tenant.');
            }

            $tax_rate = 0;
            $sub_taxes = [];

            foreach ($taxes as $single_tax) {
                $sub_taxes[] = [
                    'id' => $single_tax->id,
                    'name' => $single_tax->name,
                    'rate' => $single_tax->rate,
                ];
                $tax_rate += $single_tax->rate;
            }

            $tax = $tax->update([
                'rate' => $tax_rate,
                'sub_tax' => $sub_taxes,
                'name' => $request->name,
                'status' => $request->status,
            ]);

        } else {
            return response()->json([
                'message' => 'Invalid data format.',
            ], 406);
        }

        return response()->json([
            'message' => 'Data updated successfully.',
            'data' => $tax,
        ]);
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();

        return response()->json([
            'message' => 'Data deleted successfully',
        ]);
    }
}
