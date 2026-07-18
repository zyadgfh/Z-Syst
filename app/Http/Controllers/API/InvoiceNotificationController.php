<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InvoiceNotification;
use App\Models\Sale;
use App\Services\Invoice\InvoiceNotificationService;
use Illuminate\Http\Request;

class InvoiceNotificationController extends Controller
{
    protected InvoiceNotificationService $notificationService;

    public function __construct(InvoiceNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of notifications for a sale
     */
    public function index(Sale $sale)
    {
        $notifications = $sale->notifications()
            ->with(['customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Send invoice via specified channels
     */
    public function send(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'channels' => 'required|array',
            'channels.whatsapp' => 'sometimes|array',
            'channels.whatsapp.sendImage' => 'sometimes|boolean',
            'channels.whatsapp.sendPDF' => 'sometimes|boolean',
            'channels.whatsapp.customMessage' => 'sometimes|string',
            'channels.sms' => 'sometimes|array',
            'channels.sms.sendLink' => 'sometimes|boolean',
            'channels.sms.sendSummary' => 'sometimes|boolean',
            'channels.email' => 'sometimes|array',
            'channels.email.attachPDF' => 'sometimes|boolean',
            'channels.email.sendLink' => 'sometimes|boolean',
            'channels.email.customSubject' => 'sometimes|string',
            'channels.print' => 'sometimes|array',
            'channels.print.template' => 'sometimes|in:a4,thermal',
        ]);

        $result = $this->notificationService->sendInvoice(
            sale: $sale,
            channels: $validated['channels'],
            options: $validated
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'تم إرسال الفاتورة عبر القنوات المحددة',
        ]);
    }

    /**
     * Resend a failed notification
     */
    public function resend(InvoiceNotification $notification)
    {
        $result = $this->notificationService->resendFailed($notification);

        return response()->json([
            'success' => $result['success'] ?? false,
            'data' => $result,
            'message' => $result['success'] ? 'تم إعادة الإرسال بنجاح' : 'فشل إعادة الإرسال',
        ]);
    }

    /**
     * Get notification statistics
     */
    public function stats(Request $request)
    {
        $stats = InvoiceNotification::query()
            ->select('channel', 'status')
            ->selectRaw('count(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('channel', 'status')
            ->get();

        $totalSent = $stats->where('status', 'sent')->sum('count');
        $totalDelivered = $stats->where('status', 'delivered')->sum('count');
        $totalFailed = $stats->where('status', 'failed')->sum('count');

        $byChannel = $stats->groupBy('channel')->map(function ($group) {
            return $group->sum('count');
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_sent' => $totalSent,
                'total_delivered' => $totalDelivered,
                'total_failed' => $totalFailed,
                'delivery_rate' => $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0,
                'by_channel' => $byChannel,
            ],
        ]);
    }
}