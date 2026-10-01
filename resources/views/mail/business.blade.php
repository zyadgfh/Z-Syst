<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;padding:32px;">
                    <tr>
                        <td>
                            <h2 style="margin:0 0 16px;font-size:20px;line-height:1.4;">{{ $subjectLine }}</h2>

                            @foreach ($lines as $line)
                                <p style="margin:8px 0;font-size:14px;line-height:1.6;">{{ $line }}</p>
                            @endforeach

                            @if (! empty($table['headers']) && ! empty($table['rows']))
                                <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="margin-top:16px;border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            @foreach ($table['headers'] as $header)
                                                <th align="left" style="border-bottom:2px solid #e4e7eb;font-size:12px;text-transform:uppercase;color:#52606d;padding:6px;">{{ $header }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($table['rows'] as $row)
                                            <tr>
                                                @foreach ($row as $cell)
                                                    <td style="border-bottom:1px solid #e4e7eb;font-size:14px;padding:6px;">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            @if (! empty($footerNote))
                                <p style="margin-top:16px;font-size:13px;color:#52606d;">{{ $footerNote }}</p>
                            @endif

                            <p style="margin-top:24px;font-size:12px;color:#9aa5b1;">
                                This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
