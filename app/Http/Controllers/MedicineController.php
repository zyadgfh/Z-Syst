<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index()
    {
        return Medicine::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'category_id' => 'nullable|integer',
            'strength' => 'nullable|string',
            'dosage_form' => 'nullable|string',
            'purchase_price' => 'nullable|numeric',
            'sale_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'minimum_stock' => 'nullable|integer',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string',
        ]);

        $medicine = Medicine::create($data);

        return response()->json($medicine, 201);
    }
}
