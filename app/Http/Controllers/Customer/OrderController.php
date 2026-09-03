<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\LoyaltyPoint;
use App\Models\OrderStatusHistory;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show checkout page
     */
    public function checkout()
    {
        $cartItems = CartItem::where('user_id', Auth::id())
            ->with(['product' => fn ($q) => $q->withSum('allStocks', 'productStock')])
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('catalog.index')
                ->with('warning', __('Your cart is empty.'));
        }

        $subtotal = $cartItems->sum(fn ($item) => $item->quantity * ($item->product->getCurrentSellingPriceAttribute() ?? 0));
        $shipping = 0;
        $tax = 0;
        $total = $subtotal + $shipping + $tax;

        $user = Auth::user();

        return view('customer.checkout.index', compact('cartItems', 'subtotal', 'shipping', 'tax', 'total', 'user'));
    }

    /**
     * Place order
     */
    public function placeOrder(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'payment_method' => 'required|in:cash_on_delivery,bank_transfer',
            'notes' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string|max:50',
            'coupon_discount' => 'nullable|numeric|min:0',
        ]);

        $cartItems = CartItem::where('user_id', Auth::id())
            ->with(['product' => fn ($q) => $q->withSum('allStocks', 'productStock')])
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('catalog.index')
                ->with('warning', __('Your cart is empty.'));
        }

        // Validate stock availability (uses pre-computed withSum to avoid N+1)
        foreach ($cartItems as $item) {
            $totalStock = (int) ($item->product->all_stocks_sum_productstock ?? 0);
            if ($item->product->track_inventory && $totalStock < $item->quantity) {
                return back()->with('error', __(':product has insufficient stock.', [
                    'product' => $item->product->productName,
                ]));
            }
        }

        $subtotal = $cartItems->sum(fn ($item) => $item->quantity * ($item->product->getCurrentSellingPriceAttribute() ?? 0));
        $shipping = 0;
        $tax = 0;

        // Validate and apply coupon
        $discountAmount = 0;
        $couponModel = null;
        if ($request->filled('coupon_code') && $request->coupon_discount > 0) {
            $couponResult = Coupon::validateAndApply($request->coupon_code, $subtotal, Auth::id());
            if ($couponResult['valid']) {
                $discountAmount = $couponResult['discount'];
                $couponModel = $couponResult['coupon'] ?? null;
            }
        }

        $total = $subtotal - $discountAmount + $shipping + $tax;

        DB::beginTransaction();
        try {
            $order = CustomerOrder::create([
                'user_id' => Auth::id(),
                'business_id' => $cartItems->first()->product->business_id,
                'order_number' => CustomerOrder::generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $request->payment_method,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'city' => $request->city,
                'notes' => $request->notes,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shipping,
                'tax_amount' => $tax,
                'total_amount' => $total,
            ]);

            foreach ($cartItems as $item) {
                $unitPrice = $item->product->getCurrentSellingPriceAttribute() ?? 0;
                CustomerOrderItem::create([
                    'customer_order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->productName,
                    'product_sku' => $item->product->sku,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'total_price' => $unitPrice * $item->quantity,
                ]);
            }

            // Record initial status history
            OrderStatusHistory::record($order, 'pending', __('Order placed'), Auth::id());

            // Record coupon usage
            if ($couponModel && $discountAmount > 0) {
                CouponUsage::create([
                    'coupon_id' => $couponModel->id,
                    'user_id' => Auth::id(),
                    'customer_order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
                $couponModel->increment('times_used');
            }

            // Award loyalty points
            LoyaltyPoint::awardForOrder($order);

            // Clear cart
            CartItem::where('user_id', Auth::id())->delete();

            DB::commit();

            // ── Send Notifications (after commit) ──
            $this->sendOrderNotifications($order);

            return redirect()->route('customer.orders.show', $order->order_number)
                ->with('success', __('Order placed successfully! Order number: :number', [
                    'number' => $order->order_number,
                ]));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Failed to place order. Please try again.'));
        }
    }

    /**
     * Order confirmation / tracking page
     */
    public function show(string $orderNumber)
    {
        $order = CustomerOrder::where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->with(['items.product', 'statusHistory.changedByUser'])
            ->firstOrFail();

        // Calculate delivery estimation
        $deliveryEstimate = $this->calculateDeliveryEstimate($order);

        return view('customer.orders.show', compact('order', 'deliveryEstimate'));
    }

    /**
     * Send order confirmation email and SMS
     */
    protected function sendOrderNotifications(CustomerOrder $order): void
    {
        // Email notification
        try {
            if (config('mail.mailers.smtp.username')) {
                Mail::to($order->customer_email)
                    ->send(new OrderConfirmationMail($order));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Order confirmation email failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        // SMS notification
        try {
            if (config('sms.enabled')) {
                app(SmsService::class)->sendOrderConfirmation(
                    $order->customer_phone,
                    $order->order_number,
                    $order->total_amount
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Order confirmation SMS failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate estimated delivery date (business days)
     */
    protected function calculateDeliveryEstimate(CustomerOrder $order): array
    {
        $businessDays = match ($order->status) {
            'pending' => [3, 5],
            'confirmed' => [2, 4],
            'processing' => [2, 3],
            'shipped' => [1, 2],
            'delivered' => [0, 0],
            'cancelled' => [0, 0],
            default => [3, 5],
        };

        if ($order->status === 'delivered') {
            return [
                'min' => null,
                'max' => null,
                'label' => __('Delivered'),
                'delivered_at' => $order->updated_at,
            ];
        }

        if ($order->status === 'cancelled') {
            return [
                'min' => null,
                'max' => null,
                'label' => __('Cancelled'),
                'delivered_at' => null,
            ];
        }

        $minDate = $this->addBusinessDays(now(), $businessDays[0]);
        $maxDate = $this->addBusinessDays(now(), $businessDays[1]);

        return [
            'min' => $minDate,
            'max' => $maxDate,
            'label' => __('Estimated delivery: :min - :max', [
                'min' => $minDate->format('M d'),
                'max' => $maxDate->format('M d, Y'),
            ]),
            'delivered_at' => null,
        ];
    }

    /**
     * Add business days to a date (skip weekends)
     */
    protected function addBusinessDays(\Carbon\Carbon $date, int $days): \Carbon\Carbon
    {
        $result = $date->copy();
        $added = 0;

        while ($added < $days) {
            $result->addDay();
            if (!$result->isSaturday() && !$result->isSunday()) {
                $added++;
            }
        }

        return $result;
    }
}
