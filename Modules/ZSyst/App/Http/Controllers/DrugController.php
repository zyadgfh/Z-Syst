<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZSyst\App\Models\Drug;

class DrugController
{
    public function index(Request $request): JsonResponse
    {
        $query = Drug::query()->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('scientific_name', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->latest()->take(50)->get(),
            'count' => $query->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'purchase_price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'numeric'],
            'wholesale_price' => ['nullable', 'numeric'],
            'stock_alert' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'company_id' => ['nullable', 'integer'],
            'business_id' => ['nullable', 'integer'],
        ]);

        $drug = Drug::create($data);

        return response()->json($drug, 201);
    }

    public function show(Drug $drug): JsonResponse
    {
        return response()->json($drug->load('alternatives'));
    }
}
