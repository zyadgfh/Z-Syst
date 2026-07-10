<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;

class AcnooTaxController extends Controller
{
    public function index()
    {
        $taxes = Tax::where('business_id', auth()->user()->business_id)
                    ->when(request('type') == 'single', function ($query) {
                        $query->whereNull('sub_tax');
                    })
                    ->when(request('type') == 'group', function ($query) {
                        $query->whereNotNull('sub_tax');
                    })
                    ->when(request('status'), function ($query) {
                        $query->where('status', request('status') == 'active' ? 1 : 0);
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
            'tax_ids' => 'required_if:rate,null',
            'rate' => 'required_if:rate,null|numeric',
        ]);

        if ($request->rate && !$request->tax_ids) {

            $tax = Tax::create($request->all() + [
                'business_id' => auth()->user()->business_id,
            ]);

        } elseif (!$request->rate && $request->tax_ids) {

            $taxs = Tax::whereIn('id', $request->tax_ids)->select('id', 'name', 'rate')->get();

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

        if ($request->rate && !$request->tax_ids) {

            $tax = $tax->update($request->all());

        } elseif (!$request->rate && $request->tax_ids) {

            $taxes = Tax::whereIn('id', $request->tax_ids)->select('id', 'name', 'rate')->get();

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
