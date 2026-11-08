<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Support\Barcode\BarcodeRenderer;
use Illuminate\Http\Request;

/**
 * Dose labels — طباعة الجرعات كباركود عند البيع.
 *
 * For every sold line that carries dosing instructions the POS prints a
 * sticker: product name, the dose in large type, and a CODE128 barcode that
 * encodes DOSE:{saleId}:{detailId}. Scanning that sticker later (returns,
 * questions at the counter, patient follow-up) opens the instruction card.
 */
class DoseLabelController extends Controller
{
    /** Canonical schedules offered at the counter (code → [arabic, latin]). */
    public const SCHEDULES = [
        'OD'  => ['مرة واحدة صباحًا', 'Once daily (morning)'],
        'OD-E' => ['مرة واحدة مساءً', 'Once daily (evening)'],
        'HS'  => ['مرة قبل النوم', 'At bedtime'],
        'BID' => ['قرص صباحًا ومساءً', 'Twice daily'],
        'TID' => ['ثلاث مرات يوميًا', 'Three times daily'],
        'QID' => ['أربع مرات يوميًا', 'Four times daily'],
        'QID-AC' => ['أربع مرات قبل الأكل', 'Four times, before meals'],
        'TID-PC' => ['ثلاث مرات بعد الأكل', 'Three times, after meals'],
        'PRN' => ['عند اللزوم', 'As needed'],
        'STAT' => ['جرعة واحدة فورًا', 'Single dose now'],
    ];

    public function __construct()
    {
        $this->middleware('permission:sales-create')->only(['sheet', 'lookup']);
    }

    /**
     * Printable dose-label sheet for a sale (only lines that have a dose).
     * The POS opens this right after checkout when doses were captured.
     */
    public function sheet(Request $request, Sale $sale)
    {
        abort_unless((int) $sale->business_id === (int) $request->user()->business_id, 404);

        $lines = $sale->details()->with('product:id,productName')->get()
            ->filter(fn ($d) => filled($d->dose))
            ->values();

        $renderer = new BarcodeRenderer();
        $labels = $lines->map(function ($detail) use ($sale, $renderer) {
            // Payload must stay CODE128-B friendly (ASCII only).
            $payload = sprintf('DOSE:%d:%d', $sale->id, $detail->id);

            return [
                'id' => $detail->id,
                'product' => $detail->product?->name ?? $detail->product?->productName ?? ('#'.$detail->product_id),
                'dose' => $detail->dose,
                'dose_code' => $detail->dose_code,
                'schedule_hint' => self::SCHEDULES[$detail->dose_code][0] ?? null,
                'qty' => (int) $detail->quantities,
                'payload' => $payload,
                'svg' => $renderer->svg('CODE128', $payload, 'small'),
            ];
        });

        return response()->view('admin.pos.dose-labels', [
            'sale' => $sale,
            'labels' => $labels,
        ]);
    }

    /**
     * Scan target: DOSE:{saleId}:{detailId} → the instruction card.
     * Used by the counter scanner, the device app, and patient follow-up.
     */
    public function lookup(Request $request, ?string $payload = null)
    {
        $raw = $payload ?? (string) $request->input('code', '');
        $raw = trim(str_replace("\r", '', $raw));

        if (!preg_match('/^DOSE:(\d+):(\d+)$/i', $raw, $m)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'صيغة باركود الجرعة غير معروفة'], 422);
            }
            abort(404, 'صيغة باركود الجرعة غير معروفة');
        }

        $detail = SaleDetails::query()
            ->where('sale_id', (int) $m[1])
            ->where('id', (int) $m[2])
            ->with(['sale:id,business_id,invoiceNumber,saleDate', 'product:id,productName'])
            ->first();

        $actor = $request->user() ?: auth()->user();

        if (!$detail || !$detail->sale || !$actor || (int) $detail->sale->business_id !== (int) $actor->business_id) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'ملصق الجرعة غير موجود'], 404);
            }
            abort(404, 'ملصق الجرعة غير موجود');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'sale' => $detail->sale?->invoiceNumber ?? ('#'.$detail->sale_id),
                    'sold_at' => optional($detail->sale?->saleDate)->toDateString(),
                    'product' => $detail->product?->name ?? $detail->product?->productName,
                    'dose' => $detail->dose,
                    'dose_code' => $detail->dose_code,
                    'schedule_hint' => self::SCHEDULES[$detail->dose_code][0] ?? null,
                ],
            ]);
        }

        return view('admin.pos.dose-card', ['detail' => $detail]);
    }

    /** The schedule picker payload for the POS (select options). */
    public static function schedulesForClient(): array
    {
        return collect(self::SCHEDULES)->map(fn ($v, $k) => ['code' => $k, 'label' => $v[0]])->values()->all();
    }
}
