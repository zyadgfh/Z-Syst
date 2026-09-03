<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Order Confirmation') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f7; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #15803d 0%, #166534 100%); padding: 40px 48px; text-align: center;">
                            <div style="font-size: 32px; margin-bottom: 8px;">✅</div>
                            <h1 style="color: #ffffff; font-size: 24px; font-weight: 700; margin: 0 0 8px 0;">{{ __('Order Confirmed!') }}</h1>
                            <p style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">{{ __('Thank you for your order') }}</p>
                        </td>
                    </tr>

                    <!-- Order Number -->
                    <tr>
                        <td style="padding: 32px 48px 16px;">
                            <p style="color: #86868b; font-size: 13px; margin: 0 0 4px 0;">{{ __('Order Number') }}</p>
                            <p style="color: #1d1d1f; font-size: 20px; font-weight: 700; margin: 0;">{{ $order->order_number }}</p>
                        </td>
                    </tr>

                    <!-- Items -->
                    <tr>
                        <td style="padding: 0 48px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-top: 1px solid #f5f5f7; padding: 16px 0 8px;">
                                        <p style="color: #86868b; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Items Ordered') }}</p>
                                    </td>
                                </tr>
                                @foreach ($items as $item)
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #f5f5f7;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width: 60%; color: #1d1d1f; font-size: 14px; font-weight: 500;">
                                                    {{ $item->product_name }}
                                                    <span style="color: #86868b; font-weight: 400;"> × {{ $item->quantity }}</span>
                                                </td>
                                                <td style="width: 40%; text-align: right; color: #1d1d1f; font-size: 14px; font-weight: 600;">
                                                    ${{ number_format($item->total_price, 2) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <!-- Total -->
                    <tr>
                        <td style="padding: 0 48px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f5f5f7; border-radius: 12px; padding: 20px;">
                                <tr>
                                    <td style="padding: 12px 20px; color: #86868b; font-size: 14px;">{{ __('Subtotal') }}</td>
                                    <td style="padding: 12px 20px; text-align: right; color: #1d1d1f; font-size: 14px;">${{ number_format($order->subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 20px; color: #86868b; font-size: 14px;">{{ __('Shipping') }}</td>
                                    <td style="padding: 12px 20px; text-align: right; color: #1d1d1f; font-size: 14px;">${{ number_format($order->shipping_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px 12px; border-top: 2px solid #e5e5ea; color: #1d1d1f; font-size: 16px; font-weight: 700;">{{ __('Total') }}</td>
                                    <td style="padding: 16px 20px 12px; border-top: 2px solid #e5e5ea; text-align: right; color: #15803d; font-size: 18px; font-weight: 700;">${{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Shipping Info -->
                    <tr>
                        <td style="padding: 0 48px 32px;">
                            <p style="color: #86868b; font-size: 13px; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Shipping Address') }}</p>
                            <p style="color: #1d1d1f; font-size: 14px; margin: 0; line-height: 1.6;">
                                {{ $order->customer_name }}<br>
                                {{ $order->shipping_address }}<br>
                                {{ $order->city ?? '' }}
                            </p>
                        </td>
                    </tr>

                    <!-- Track Order Button -->
                    <tr>
                        <td style="padding: 0 48px 40px; text-align: center;">
                            <a href="{{ route('customer.orders.show', $order->order_number) }}" style="display: inline-block; background: #15803d; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-size: 15px; font-weight: 600;">{{ __('Track Your Order') }}</a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f5f5f7; padding: 24px 48px; text-align: center;">
                            <p style="color: #86868b; font-size: 12px; margin: 0;">
                                {{ __('If you have any questions, please contact us.') }}<br>
                                © {{ date('Y') }} {{ config('app.name', 'Z-Syst') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
