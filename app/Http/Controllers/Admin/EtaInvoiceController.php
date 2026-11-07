<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EtaInvoice;
use App\Models\Sale;
use App\Models\BusinessSetting;
use App\Services\EtaInvoiceService;
use Illuminate\Http\Request;

class EtaInvoiceController extends Controller
{
    public function __construct(protected EtaInvoiceService $eta)
    {
        $this->middleware('permission:eta-read')->only(['index', 'settings']);
        $this->middleware('permission:eta-create')->only(['submit', 'retry']);
        $this->middleware('permission:eta-update')->only('saveSettings');
    }

    public function index(Request $request)
    {
        return view('admin.eta.index', [
            'invoices' => EtaInvoice::forBusiness((int) $request->user()->business_id)
                ->with('sale:id,invoiceNumber,totalAmount,saleDate')->latest('id')->paginate(15),
            'driver' => $this->eta->driver((int) $request->user()->business_id),
            'recentSales' => Sale::forBusiness((int) $request->user()->business_id)->latest('id')->limit(20)->get(['id', 'invoiceNumber', 'totalAmount']),
        ]);
    }

    public function settings(Request $request)
    {
        $businessId = (int) $request->user()->business_id;
        $keys = ['eta_driver', 'eta_client_id', 'eta_issuer_rin', 'eta_issuer_name', 'eta_activity_code', 'eta_sandbox', 'eta_governate', 'eta_city', 'eta_street', 'eta_building', 'eta_postal'];

        return response()->json(['data' => collect($keys)->mapWithKeys(fn ($k) => [$k => BusinessSetting::get($businessId, $k)])]);
    }

    public function saveSettings(Request $request)
    {
        $businessId = (int) $request->user()->business_id;
        $data = $request->validate([
            'eta_driver' => 'nullable|in:simulation,api',
            'eta_client_id' => 'nullable|string|max:120',
            'eta_client_secret' => 'nullable|string|max:200',
            'eta_issuer_rin' => 'nullable|string|max:20',
            'eta_issuer_name' => 'nullable|string|max:120',
            'eta_activity_code' => 'nullable|string|max:12',
            'eta_sandbox' => 'nullable|boolean',
            'eta_governate' => 'nullable|string|max:60',
            'eta_city' => 'nullable|string|max:60',
            'eta_street' => 'nullable|string|max:120',
            'eta_building' => 'nullable|string|max:20',
            'eta_postal' => 'nullable|string|max:12',
        ]);

        foreach ($data as $key => $value) {
            BusinessSetting::put($businessId, $key, $value);
        }

        return response()->json(['success' => true]);
    }

    public function submit(Request $request, Sale $sale)
    {
        $invoice = $this->eta->submit($sale);

        return response()->json(['success' => true, 'data' => $invoice]);
    }
}
