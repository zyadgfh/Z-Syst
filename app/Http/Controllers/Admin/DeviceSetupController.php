<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Printer;
use App\Services\DeviceRegistryService;
use App\Services\DeviceSettings;
use App\Services\NetworkDeviceDiscovery;
use Illuminate\Http\Request;

/**
 * DeviceSetupController — the settings hub for devices, printers/scanners
 * and every enable/disable preference of the module.
 */
class DeviceSetupController extends Controller
{
    public function __construct(
        protected DeviceRegistryService $registry,
        protected NetworkDeviceDiscovery $discovery,
    ) {
        $this->middleware('permission:devices-read')->only('index', 'current', 'networkScan');
        $this->middleware('permission:devices-create')->only('enroll', 'approve', 'storePrinter');
        $this->middleware('permission:devices-update')->only('updateDevice', 'updatePreferences', 'updatePrinter');
        $this->middleware('permission:devices-delete')->only('destroyDevice', 'destroyPrinter');
    }

    /** Settings hub: devices + printers + preferences tabs. */
    public function index(Request $request)
    {
        $businessId = (int) $request->user()->business_id;
        $current = $this->registry->resolve($request);
        $current->load(['barcodePrinter', 'receiptPrinter']);

        $devices = Device::forBusiness($businessId)
            ->with(['barcodePrinter', 'receiptPrinter'])
            ->orderByDesc('last_seen_at')
            ->get();

        $printers = Printer::forBusiness($businessId)
            ->orderBy('type')->orderBy('name')
            ->get()
            ->each(function (Printer $printer) use ($businessId) {
                $printer->attached_devices_count = Device::forBusiness($businessId)
                    ->where(fn ($q) => $q->where('barcode_printer_id', $printer->id)->orWhere('receipt_printer_id', $printer->id))
                    ->count();
            });

        return view('admin.device-setup.index', [
            'current' => $current,
            'devices' => $devices,
            'printers' => $printers,
            'settings' => DeviceSettings::all($businessId),
            'types' => [Device::TYPE_POS, Device::TYPE_TABLET, Device::TYPE_PC, Device::TYPE_MOBILE, Device::TYPE_OTHER],
            'printerTypes' => [Printer::TYPE_BARCODE, Printer::TYPE_RECEIPT, Printer::TYPE_LABEL],
            'connections' => [Printer::CONNECTION_NETWORK, Printer::CONNECTION_USB, Printer::CONNECTION_BLUETOOTH, Printer::CONNECTION_SYSTEM],
        ]);
    }

    /** JSON: the caller's resolved device + printers + preferences (POS UI). */
    public function current(Request $request)
    {
        $device = $this->registry->resolve($request);
        $businessId = (int) $request->user()->business_id;

        return response()->json([
            'success' => true,
            'data' => array_merge(
                $device?->clientPayload() ?? ['device' => null, 'printers' => ['barcode' => null, 'receipt' => null]],
                ['settings' => DeviceSettings::clientPayload($businessId)],
                ['approved' => (bool) ($device?->is_approved ?? true)]
            ),
        ]);
    }

    /** Register the caller's device explicitly (same fingerprint flow). */
    public function enroll(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'type' => 'nullable|in:pos,tablet,pc,mobile,other',
            'peripherals' => 'nullable|array',
        ]);

        $device = $this->registry->resolve($request)
            ?? $this->registry->enroll(
                $request,
                $validated['name'],
                $validated['type'] ?? Device::TYPE_PC,
                $validated['peripherals'] ?? []
            );

        $device->fill(['name' => $validated['name']])->save();
        $device->refresh();

        return back()->with('success', __('تم تسجيل الجهاز: :name', ['name' => $device->name]));
    }

    public function approve(Request $request, Device $device)
    {
        $this->authorizeDevice($request, $device);
        $device->forceFill(['is_approved' => ! $device->is_approved])->save();

        return back()->with('success', __('حالة الجهاز حُدثت.'));
    }

    public function updateDevice(Request $request, Device $device)
    {
        $this->authorizeDevice($request, $device);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:60',
            'type' => 'sometimes|in:pos,tablet,pc,mobile,other',
            'has_scanner' => 'sometimes|boolean',
            'barcode_printer_id' => 'nullable|integer',
            'receipt_printer_id' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        foreach (['barcode_printer_id', 'receipt_printer_id'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field]) {
                Printer::forBusiness((int) $request->user()->business_id)->findOrFail($validated[$field]);
            }
        }

        $device->update($validated);

        return back()->with('success', __('بيانات الجهاز حُدثت.'));
    }

    public function destroyDevice(Request $request, Device $device)
    {
        $this->authorizeDevice($request, $device);
        $device->delete();

        return back()->with('success', __('الجهاز حُذف.'));
    }

    public function storePrinter(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:60',
            'type' => 'required|in:barcode,receipt,label',
            'connection' => 'required|in:network,usb,bluetooth,system',
            'host' => 'nullable|string|max:120',
            'vendor' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:60',
            'is_default_barcode' => 'sometimes|boolean',
            'is_default_receipt' => 'sometimes|boolean',
        ]);

        $businessId = (int) $request->user()->business_id;
        $printer = Printer::create($validated + ['business_id' => $businessId]);
        $this->applyDefaults($printer, (bool) ($validated['is_default_barcode'] ?? false), (bool) ($validated['is_default_receipt'] ?? false));

        return back()->with('success', __('الطابعة أُضيفت.'));
    }

    public function updatePrinter(Request $request, Printer $printer)
    {
        abort_unless((int) $printer->business_id === (int) $request->user()->business_id, 404);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:60',
            'connection' => 'sometimes|in:network,usb,bluetooth,system',
            'host' => 'nullable|string|max:120',
            'vendor' => 'nullable|string|max:60',
            'model' => 'nullable|string|max:60',
            'is_active' => 'sometimes|boolean',
            'is_default_barcode' => 'sometimes|boolean',
            'is_default_receipt' => 'sometimes|boolean',
        ]);

        $printer->update($validated);
        $this->applyDefaults($printer, (bool) ($validated['is_default_barcode'] ?? $printer->is_default_barcode), (bool) ($validated['is_default_receipt'] ?? $printer->is_default_receipt));

        return back()->with('success', __('الطابعة حُدثت.'));
    }

    public function destroyPrinter(Request $request, Printer $printer)
    {
        abort_unless((int) $printer->business_id === (int) $request->user()->business_id, 404);
        $printer->delete();

        return back()->with('success', __('الطابعة حُذفت.'));
    }

    /** Print a self-test label/receipt through the renderer (browser prints it). */
    public function testPrinter(Request $request, Printer $printer)
    {
        abort_unless((int) $printer->business_id === (int) $request->user()->business_id, 404);

        if ($printer->type === Printer::TYPE_RECEIPT) {
            return redirect()->route('admin.barcodes.labels', ['ids' => \App\Models\Barcode::where('business_id', $printer->business_id)->value('id') ?? 0])
                ->with('info', __('معاينة طباعة الإيصال عبر شيت الملصقات (الطابعة: :n).', ['n' => $printer->name]));
        }

        $number = \App\Models\Barcode::generateBarcodeNumber('CODE128');
        $renderer = new \App\Support\Barcode\BarcodeRenderer();

        return response($renderer->svg('CODE128', $number, 'standard'), 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'inline; filename="printer-test.svg"');
    }

    /** LAN inventory (opt-in, self-host capable runtimes only). */
    public function networkScan(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        if (! DeviceSettings::get($businessId, 'devices.network_scan', false)) {
            return response()->json([
                'success' => false,
                'message' => __('المسح الشبكي مُعطّل — فعّله من تبويب التفضيلات (تشغيل ذاتي داخل شبكة الصيدلية فقط).'),
            ], 400);
        }

        $result = $this->discovery->discover();

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function updatePreferences(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        $validated = $request->validate([
            'devices.auto_register' => 'sometimes|boolean',
            'devices.require_approval' => 'sometimes|boolean',
            'devices.network_scan' => 'sometimes|boolean',
            'scanner.auto_add' => 'sometimes|boolean',
            'scanner.auto_focus' => 'sometimes|boolean',
            'scanner.beep' => 'sometimes|boolean',
            'scanner.min_chars' => 'sometimes|integer|min:2|max:20',
            'scanner.max_input_ms' => 'sometimes|integer|min:50|max:1000',
            'print.barcode_on_purchase_complete' => 'sometimes|boolean',
            'print.barcode_on_sale' => 'sometimes|boolean',
            'print.receipt_on_sale' => 'sometimes|in:auto,ask,never',
            'print.default_symbology' => 'sometimes|in:CODE128,EAN13,UPC,QR',
            'print.default_size' => 'sometimes|in:small,standard,large',
            'print.max_labels_per_item' => 'sometimes|integer|min:1|max:50',
        ]);

        DeviceSettings::update($businessId, $validated);

        return back()->with('success', __('التفضيلات حُفظت.'));
    }

    protected function applyDefaults(Printer $printer, bool $barcodeDefault, bool $receiptDefault): void
    {
        if ($barcodeDefault) {
            Printer::forBusiness((int) $printer->business_id)
                ->where('id', '!=', $printer->id)->update(['is_default_barcode' => false]);
            $printer->forceFill(['is_default_barcode' => true])->save();
        }
        if ($receiptDefault) {
            Printer::forBusiness((int) $printer->business_id)
                ->where('id', '!=', $printer->id)->update(['is_default_receipt' => false]);
            $printer->forceFill(['is_default_receipt' => true])->save();
        }
    }

    protected function authorizeDevice(Request $request, Device $device): void
    {
        abort_unless((int) $device->business_id === (int) $request->user()->business_id, 404);
    }
}
