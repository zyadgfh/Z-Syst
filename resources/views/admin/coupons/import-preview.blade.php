@extends('layouts.master')

@section('title', 'معاينة استيراد الكوبونات')

@section('main_content')
<div class="container-fluid" style="padding: 24px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.coupons.import') }}" style="color: #007aff; text-decoration: none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 style="font-weight: 700; margin: 0;">معاينة الاستيراد — {{ count($validated) }} كوبون</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.coupons.import') }}" class="btn" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px 20px; font-weight: 600; text-decoration: none;">إلغاء</a>
            @if (count($validated) > 0)
                <form action="{{ route('admin.coupons.import.confirm') }}" method="POST" style="display: inline;" onsubmit="return confirm('تأكيد استيراد {{ count($validated) }} كوبون؟')">
                    @csrf
                    <button type="submit" class="btn" style="background: #34c759; color: #fff; border-radius: 10px; padding: 10px 20px; font-weight: 600; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align: middle; margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg>
                        تأكيد الاستيراد ({{ count($validated) }})
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Errors --}}
    @if (!empty($errors))
        <div style="background: #fff3cd; color: #856404; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 14px;">
            <strong>{{ count($errors) }} صف(صفوف) بها أخطاء:</strong>
            <ul class="mb-0 mt-2" style="list-style: none; padding: 0;">
                @foreach ($errors as $line => $lineErrors)
                    <li>سطر {{ $line }}: {{ implode(', ', $lineErrors) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Preview Table --}}
    @if (count($validated) > 0)
        <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 13px;">
                    <thead style="background: #f5f5f7;">
                        <tr>
                            <th style="border: none; padding: 10px 12px; font-weight: 600; color: #6e6e73; font-size: 11px; text-transform: uppercase;">سطر</th>
                            <th style="border: none; padding: 10px 12px; font-weight: 600; color: #6e6e73; font-size: 11px; text-transform: uppercase;">الكود</th>
                            <th style="border: none; padding: 10px 12px; font-weight: 600; color: #6e6e73; font-size: 11px; text-transform: uppercase;">النوع</th>
                            <th style="border: none; padding: 10px 12px; font-weight: 600; color: #6e6e73; font-size: 11px; text-transform: uppercase;">القيمة</th>
                            <th style="border: none; padding: 10px 12px; font-weight: 600; color: #6e6e73; font-size: 11px; text-transform: uppercase;">الحد الأدنى</th>
                            <th style="border: none; padding: 10px 12px; font-weight: 600; color: #6e6e73; font-size: 11px; text-transform: uppercase;">حد الاستخدام</th>
                            <th style="border: none; padding: 10px 12px; font-weight: 600; color: #6e6e73; font-size: 11px; text-transform: uppercase;">الصلاحية</th>
                            <th style="border: none; padding: 10px 12px; font-weight: 600; color: #6e6e73; font-size: 11px; text-transform: uppercase;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($validated as $row)
                            <tr style="border-bottom: 1px solid #f0f0f2;">
                                <td style="padding: 10px 12px; color: #86868b;">{{ $row['line'] }}</td>
                                <td style="padding: 10px 12px;"><code style="background: #f5f5f7; padding: 3px 8px; border-radius: 4px; font-weight: 600;">{{ $row['code'] }}</code></td>
                                <td style="padding: 10px 12px;">
                                    @if ($row['type'] === 'percentage')
                                        <span style="background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">{{ $row['value'] }}%</span>
                                    @else
                                        <span style="background: #e3f2fd; color: #1565c0; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${{ number_format($row['value'], 2) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 12px;">${{ number_format($row['minimum_order_amount'], 2) }}</td>
                                <td style="padding: 10px 12px;">{{ $row['usage_limit'] ?? '∞' }}</td>
                                <td style="padding: 10px 12px;">{{ $row['expires_at'] ?? '—' }}</td>
                                <td style="padding: 10px 12px;">{{ $row['active'] ? '✓' : '✗' }}</td>
                                <td style="padding: 10px 12px; color: #34c759;">جاهز</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="text-center py-5" style="color: #86868b;">
            <p style="font-size: 16px;">لا توجد كوبونات صالحة للاستيراد</p>
        </div>
    @endif
</div>
@endsection
