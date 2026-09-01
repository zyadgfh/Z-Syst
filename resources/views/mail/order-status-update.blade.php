<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Order Update') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f7; font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1d1d1f 0%, #424245 100%); padding: 40px 48px; text-align: center;">
                            <div style="font-size: 32px; margin-bottom: 8px;">
                                {{ match($order->status) {
                                    'confirmed' => '✅',
                                    'processing' => '⚙️',
                                    'shipped' => '🚚',
                                    'delivered' => '📦',
                                    'cancelled' => '❌',
                                    default => '📋',
                                } }}
                            </div>
                            <h1 style="color: #ffffff; font-size: 24px; font-weight: 700; margin: 0 0 8px 0;">{{ __('Order Update') }}</h1>
                            <p style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 0;">{{ __('Your order status has been updated') }}</p>
                        </td>
                    </tr>

                    <!-- Order Number + Status -->
                    <tr>
                        <td style="padding: 32px 48px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="width: 50%;">
                                        <p style="color: #86868b; font-size: 13px; margin: 0 0 4px 0;">{{ __('Order Number') }}</p>
                                        <p style="color: #1d1d1f; font-size: 18px; font-weight: 700; margin: 0;">{{ $order->order_number }}</p>
                                    </td>
                                    <td style="width: 50%; text-align: right;">
                                        <p style="color: #86868b; font-size: 13px; margin: 0 0 4px 0;">{{ __('New Status') }}</p>
                                        <span style="display: inline-block; background: {{ match($order->status) {
                                            'confirmed' => '#dcfce7; color: #166534',
                                            'processing' => '#ede9fe; color: #5b21b6',
                                            'shipped' => '#dbeafe; color: #1e40af',
                                            'delivered' => '#f0fdf4; color: #166534',
                                            'cancelled' => '#fef2f2; color: #991b1b',
                                            default => '#f3f4f6; color: #374151',
                                        } }}; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Status Timeline -->
                    <tr>
                        <td style="padding: 0 48px 32px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                @php
                                    $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                                    $currentIndex = array_search($order->status, $statuses);
                                    if ($currentIndex === false) $currentIndex = -1;
                                @endphp
                                @foreach ($statuses as $index => $s)
                                <tr>
                                    <td style="width: 30px; vertical-align: top; padding-top: 2px;">
                                        @if ($index <= $currentIndex)
                                            <div style="width: 24px; height: 24px; border-radius: 50%; background: #15803d; color: #fff; font-size: 12px; text-align: center; line-height: 24px;">✓</div>
                                        @else
                                            <div style="width: 24px; height: 24px; border-radius: 50%; background: #e5e7eb; color: #9ca3af; font-size: 12px; text-align: center; line-height: 24px;">{{ $index + 1 }}</div>
                                        @endif
                                    </td>
                                    <td style="padding: 0 0 16px 12px; {{ $index < count($statuses) - 1 ? 'border-left: 2px solid ' . ($index < $currentIndex ? '#15803d' : '#e5e7eb') . '; margin-left: 11px; padding-left: 24px;' : '' }}">
                                        <span style="font-size: 14px; font-weight: {{ $index <= $currentIndex ? '600' : '400' }}; color: {{ $index <= $currentIndex ? '#1d1d1f' : '#86868b' }};">
                                            {{ ucfirst($s) }}
                                        </span>
                                        @if ($order->status === $s)
                                            <span style="font-size: 12px; color: #86868b; margin-left: 8px;">← {{ __('Current') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <!-- Total -->
                    <tr>
                        <td style="padding: 0 48px 24px;">
                            <div style="background: #f5f5f7; border-radius: 12px; padding: 16px 20px; display: flex; justify-content: space-between;">
                                <span style="color: #1d1d1f; font-size: 15px; font-weight: 600;">{{ __('Total') }}</span>
                                <span style="color: #15803d; font-size: 18px; font-weight: 700;">${{ number_format($order->total_amount, 2) }}</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Track Order Button -->
                    <tr>
                        <td style="padding: 0 48px 40px; text-align: center;">
                            <a href="{{ route('customer.orders.show', $order->order_number) }}" style="display: inline-block; background: #1d1d1f; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-size: 15px; font-weight: 600;">{{ __('Track Your Order') }}</a>
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
