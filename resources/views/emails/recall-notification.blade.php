<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ __('Drug Recall Notice') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #dc3545; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="margin: 0; font-size: 24px;">🚨 {{ __('Drug Recall Notice') }}</h1>
    </div>

    <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6;">
        <p>{{ __('Dear :name,', ['name' => $context['customer_name']]) }}</p>

        <p>{{ __('We are writing to inform you that a product you recently purchased has been subject to a recall. Your safety is our top priority.') }}</p>

        <div style="background-color: white; border: 1px solid #dee2e6; border-radius: 6px; padding: 16px; margin: 16px 0;">
            <h3 style="margin-top: 0; color: #dc3545;">{{ __('Recall Details') }}</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px 0; font-weight: bold; width: 140px;">{{ __('Product:') }}</td>
                    <td style="padding: 6px 0;">{{ $context['product_name'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; font-weight: bold;">{{ __('Batch Number:') }}</td>
                    <td style="padding: 6px 0;">{{ $context['batch_number'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; font-weight: bold;">{{ __('Reason:') }}</td>
                    <td style="padding: 6px 0;">{{ $context['reason'] }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; font-weight: bold;">{{ __('Recall Date:') }}</td>
                    <td style="padding: 6px 0;">{{ $context['recall_date'] }}</td>
                </tr>
                @if($context['invoice_numbers'])
                <tr>
                    <td style="padding: 6px 0; font-weight: bold;">{{ __('Your Invoice(s):') }}</td>
                    <td style="padding: 6px 0;">{{ $context['invoice_numbers'] }}</td>
                </tr>
                @endif
            </table>
        </div>

        @if($context['description'])
        <div style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px; margin: 16px 0;">
            <strong>{{ __('Additional Information:') }}</strong>
            <p style="margin: 6px 0 0 0;">{{ $context['description'] }}</p>
        </div>
        @endif

        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; padding: 12px; margin: 16px 0;">
            <strong>{{ __('⚠️ Immediate Action Required:') }}</strong>
            <ul style="margin: 6px 0 0 0; padding-left: 20px;">
                <li>{{ __('Stop using this product immediately') }}</li>
                <li>{{ __('Do not use any remaining quantity of this product') }}</li>
                <li>{{ __('Return the product to our pharmacy for a full refund') }}</li>
                <li>{{ __('If you have experienced any adverse effects, contact your healthcare provider immediately') }}</li>
            </ul>
        </div>

        <p>{{ __('We sincerely apologize for any inconvenience this may cause. Please do not hesitate to contact us if you have any questions.') }}</p>

        <p>{{ __('Thank you for your understanding.') }}</p>
    </div>

    <div style="background-color: #e9ecef; padding: 16px; border-radius: 0 0 8px 8px; text-align: center; font-size: 12px; color: #6c757d;">
        <p style="margin: 0;">{{ __('This is an automated recall notification. Please do not reply to this email.') }}</p>
    </div>
</body>
</html>
