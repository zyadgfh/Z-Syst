<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Live sales board over Server-Sent Events — no websocket package needed
 * (works on every host incl. php-wasm): the browser subscribes once and the
 * server streams new sales since the last id every 3s.
 */
class LiveController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:live-view');
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        return view('admin.live.index', [
            'today' => Sale::forBusiness($businessId)->whereDate('saleDate', now()->toDateString()),
            'lastId' => (int) (Sale::forBusiness($businessId)->max('id') ?? 0),
        ]);
    }

    public function stream(Request $request)
    {
        $businessId = (int) $request->user()->business_id;
        $after = (int) $request->query('after', 0);

        return Response::stream(function () use ($businessId, $after) {
            $cursor = $after;
            $deadline = time() + 55; // recycle the stream; browser EventSource reconnects

            echo "retry: 3000\n\n";
            while (time() < $deadline) {
                $sales = Sale::forBusiness($businessId)->where('id', '>', $cursor)
                    ->latest('id')->limit(20)->get(['id', 'invoiceNumber', 'totalAmount', 'paymentType', 'saleDate', 'created_at']);

                foreach ($sales as $sale) {
                    $cursor = max($cursor, $sale->id);
                    echo 'event: sale'. "\n";
                    echo 'data: '.json_encode([
                        'id' => $sale->id, 'invoice' => $sale->invoiceNumber,
                        'total' => (float) $sale->totalAmount, 'payment' => $sale->paymentType,
                        'at' => optional($sale->created_at)->format('H:i:s'),
                    ])."\n\n";
                }

                if ($sales->isNotEmpty()) {
                    echo 'event: totals'. "\n";
                    echo 'data: '.json_encode([
                        'today_total' => (float) Sale::forBusiness($businessId)->whereDate('saleDate', now()->toDateString())->sum('totalAmount'),
                        'today_count' => (int) Sale::forBusiness($businessId)->whereDate('saleDate', now()->toDateString())->count(),
                        'last_id' => $cursor,
                    ])."\n\n";
                } else {
                    // keep-alive ping
                    echo ": ping ".now()->format('H:i:s')."\n\n";
                }

                if (connection_aborted()) {
                    return;
                }
                flush();
                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
