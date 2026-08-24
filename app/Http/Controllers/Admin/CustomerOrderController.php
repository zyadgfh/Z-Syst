<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusMail;
use App\Models\CustomerOrder;
use App\Models\OrderStatusHistory;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomerOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:orders-read')->only('index', 'show');
        $this->middleware('permission:orders-update')->only('updateStatus', 'updatePayment');
    }

    /**
     * List all customer orders
     */
    public function index(Request $request)
    {
        $query = CustomerOrder::with('items.product', 'user');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $orders = $query->latest()->paginate(15);

        $stats = [
            'total' => CustomerOrder::count(),
            'pending' => CustomerOrder::where('status', 'pending')->count(),
            'processing' => CustomerOrder::where('status', 'processing')->count(),
            'shipped' => CustomerOrder::where('status', 'shipped')->count(),
            'delivered' => CustomerOrder::where('status', 'delivered')->count(),
            'cancelled' => CustomerOrder::where('status', 'cancelled')->count(),
            'revenue' => CustomerOrder::where('payment_status', 'paid')->sum('total_amount'),
        ];

        return view('admin.customer-orders.index', compact('orders', 'stats'));
    }

    /**
     * View order detail with status history
     */
    public function show(CustomerOrder $customerOrder)
    {
        $customerOrder->load('items.product', 'user', 'statusHistory.changedByUser');

        return view('admin.customer-orders.show', ['order' => $customerOrder]);
    }

    /**
     * Update order status + send notifications
     */
    public function updateStatus(Request $request, CustomerOrder $customerOrder)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'note' => 'nullable|string|max:500',
        ]);

        $previousStatus = $customerOrder->status;

        if ($customerOrder->markAs($request->status)) {
            // Record status history
            OrderStatusHistory::record(
                $customerOrder,
                $request->status,
                $request->input('note'),
                auth()->id()
            );

            // Send notifications
            $this->sendStatusNotifications($customerOrder, $previousStatus);

            return response()->json([
                'success' => true,
                'message' => __('Order status updated to :status.', ['status' => $request->status]),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Cannot transition from :from to :to.', [
                'from' => $customerOrder->status,
                'to' => $request->status,
            ]),
        ], 400);
    }

    /**
     * Update payment status
     */
    public function updatePayment(Request $request, CustomerOrder $customerOrder)
    {
        $request->validate([
            'payment_status' => 'required|in:unpaid,paid,partially_refunded,refunded',
        ]);

        $customerOrder->update(['payment_status' => $request->payment_status]);

        return response()->json([
            'success' => true,
            'message' => __('Payment status updated.'),
        ]);
    }

    /**
     * Export orders as CSV
     */
    public function exportCsv(Request $request)
    {
        $query = CustomerOrder::with('items');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customer_orders_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order #', 'Customer', 'Email', 'Phone', 'Status', 'Payment', 'Total', 'Date']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_email,
                    $order->customer_phone,
                    $order->status,
                    $order->payment_status,
                    $order->total_amount,
                    $order->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Send status change notifications
     */
    protected function sendStatusNotifications(CustomerOrder $order, string $previousStatus): void
    {
        // Email
        try {
            if (config('mail.mailers.smtp.username')) {
                Mail::to($order->customer_email)
                    ->send(new OrderStatusMail($order, $previousStatus));
            }
        } catch (\Throwable $e) {
            Log::error('Order status email failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        // SMS
        try {
            if (config('sms.enabled')) {
                app(SmsService::class)->sendOrderStatusUpdate(
                    $order->customer_phone,
                    $order->order_number,
                    $order->status
                );
            }
        } catch (\Throwable $e) {
            Log::error('Order status SMS failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
