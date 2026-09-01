<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نقاط ولائك على وشك الانتهاء</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f7; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f7; padding: 32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #ff9500, #ff6b00); padding: 32px; text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 8px;">⏰</div>
                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 700; margin: 0;">نقاط ولائك على وشك الانتهاء!</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="font-size: 16px; color: #1d1d1f; line-height: 1.8; margin: 0 0 16px;">
                                مرحباً <strong>{{ $user->name }}</strong>،
                            </p>

                            <p style="font-size: 15px; color: #3a3a3c; line-height: 1.8; margin: 0 0 24px;">
                                نود إبلاغك بأن لديك نقاط ولاء على وشك الانتهاء. يرجى استخدامها قبل فوات الأوان.
                            </p>

                            {{-- Expiry Card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background: #fff5e6; border: 1px solid #ffe0b2; border-radius: 12px; padding: 24px; text-align: center;">
                                        <div style="font-size: 13px; color: #e65100; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">النقاط التي ستنتهي</div>
                                        <div style="font-size: 42px; font-weight: 800; color: #ff6b00; margin-bottom: 4px;">{{ number_format($totalExpiringPoints) }}</div>
                                        <div style="font-size: 14px; color: #bf360c;">تنتهي خلال {{ $daysUntilExpiry }} يوم</div>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 14px; color: #6e6e73; line-height: 1.8; margin: 0 0 24px;">
                                يمكنك استبدال نقاطك بكوبونات خصم أو استخدامها مباشرة عند الشراء. لا تدع نقاطك تضيع!
                            </p>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ route('customer.loyalty') }}" style="display: inline-block; background: #ff6b00; color: #ffffff; font-size: 16px; font-weight: 700; text-decoration: none; padding: 14px 40px; border-radius: 10px;">
                                            استخدم نقاطك الآن
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background: #f5f5f7; padding: 24px 32px; text-align: center; border-top: 1px solid #e5e5ea;">
                            <p style="font-size: 12px; color: #86868b; margin: 0;">
                                هذه رسالة تلقائية من نظام {{ config('app.name') }} — نقاط الولاء تنتهي صلاحيتها بعد 12 شهراً من تاريخ الاستلام.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
