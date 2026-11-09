<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Gateway;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings-read')->only(['index','show']);
        $this->middleware('permission:settings-update')->only(['update']);
    }

    use HasUploader;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $gateways = Gateway::all();
        $currencies = Currency::latest()->get();

        return view('admin.gateways.index', compact('gateways', 'currencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            'charge' => 'nullable|numeric',
            'currency_id' => 'nullable|exists:currencies,id',
            'image' => 'nullable|image|max:2048',
        ]);
        $gateway = Gateway::findOrFail($id);
        $data = $request->only(['name','status','charge','currency_id','data','namespace','is_manual']);
        if ($request->hasFile('image')) {
            $data['image'] = $this->upload($request, 'image', $gateway->image);
        }
        $gateway->update($data);

        return response()->json('Gateway updated successfully');
    }
}
