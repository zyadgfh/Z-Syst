<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Printer;
use App\Services\NetworkDeviceDiscovery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PrinterController — CRUD for the business's printers + connectivity test.
 * Used by the Flutter POS ("attached printer" setup) and the admin hub.
 */
class PrinterController extends Controller
{
    public function __construct(protected NetworkDeviceDiscovery $discovery)
    {
        $this->middleware('permission:printers-read')->only('index', 'test');
        $this->middleware('permission:printers-create')->only('store');
        $this->middleware('permission:printers-update')->only('update');
        $this->middleware('permission:printers-delete')->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $printers = Printer::forBusiness((int) $request->user()->business_id)
            ->ofType($request->type)
            ->orderBy('type')->orderBy('name')->get();

        return response()->json(['success' => true, 'data' => $printers]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'type' => 'required|in:barcode,receipt,label',
            'connection' => 'required|in:network,usb,bluetooth,system',
            'host' => 'nullable|string|max:120',
            'vendor' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:60',
            'is_default_barcode' => 'nullable|boolean',
            'is_default_receipt' => 'nullable|boolean',
        ]);

        $printer = Printer::create($validated + ['business_id' => (int) $request->user()->business_id]);

        return response()->json(['success' => true, 'message' => 'Printer created', 'data' => $printer], 201);
    }

    public function update(Request $request, Printer $printer): JsonResponse
    {
        abort_unless((int) $printer->business_id === (int) $request->user()->business_id, 404);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:60',
            'connection' => 'sometimes|in:network,usb,bluetooth,system',
            'host' => 'nullable|string|max:120',
            'is_active' => 'sometimes|boolean',
            'is_default_barcode' => 'sometimes|boolean',
            'is_default_receipt' => 'sometimes|boolean',
        ]);

        $printer->update($validated);

        return response()->json(['success' => true, 'data' => $printer->fresh()]);
    }

    public function destroy(Request $request, Printer $printer): JsonResponse
    {
        abort_unless((int) $printer->business_id === (int) $request->user()->business_id, 404);
        $printer->delete();

        return response()->json(['success' => true, 'message' => 'Printer deleted']);
    }

    /**
     * POST /printers/{printer}/test — connectivity + a rendered sample.
     * Network printers get a live TCP probe; everything else receives the
     * sample the client should push to its local driver/connection.
     */
    public function test(Request $request, Printer $printer): JsonResponse
    {
        abort_unless((int) $printer->business_id === (int) $request->user()->business_id, 404);

        $probe = null;
        if ($printer->connection === Printer::CONNECTION_NETWORK && $printer->host) {
            $probe = $this->discovery->probeHost($printer->host);
            $printer->forceFill(['last_probe_at' => now()])->save();
        }

        $renderer = new \App\Support\Barcode\BarcodeRenderer();
        $number = \App\Models\Barcode::generateBarcodeNumber('EAN13');

        return response()->json([
            'success' => true,
            'data' => [
                'probe' => $probe,
                'sample' => [
                    'kind' => $printer->type === Printer::TYPE_RECEIPT ? 'text' : 'image',
                    'text' => $printer->type === Printer::TYPE_RECEIPT
                        ? "Z-Syst TEST\n".$printer->name."\n".now()->toDateTimeString()."\n\n"
                        : null,
                    'barcode_number' => $number,
                    'barcode_svg' => $printer->type === Printer::TYPE_RECEIPT ? null
                        : $renderer->svg('CODE128', $number, $printer->settings['size'] ?? 'standard'),
                ],
            ],
        ]);
    }
}
