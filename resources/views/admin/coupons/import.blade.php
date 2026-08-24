@extends('layouts.master')

@section('title', 'استيراد كوبونات بالجملة')

@section('main_content')
<div class="container-fluid" style="padding: 24px; max-width: 800px;">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.coupons.index') }}" style="color: #007aff; text-decoration: none; margin-left: 16px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <h4 style="font-weight: 700; margin: 0;">استيراد كوبونات من ملف CSV</h4>
    </div>

    @if ($errors->any())
        <div style="background: #fff0f0; color: #ff3b30; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 14px;">
            <ul class="mb-0" style="list-style: none; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Instructions --}}
    <div class="card mb-4" style="border-radius: 14px; border: 1px solid #e5e5ea;">
        <div class="card-body" style="padding: 20px;">
            <h6 style="font-weight: 600; margin-bottom: 12px;">الخطوات:</h6>
            <ol style="font-size: 14px; color: #424245; line-height: 2; padding-inline-start: 20px;">
                <li>حمّل ملف العينة للإطلاع على التنسيق المطلوب</li>
                <li>املأ الملف بالبيانات (الكود والنوع والقيمة مطلوبة)</li>
                <li>ارفع الملف هنا للمعاينة</li>
                <li>راجع النتائج ثم اضغط "تأكيد الاستيراد"</li>
            </ol>

            <a href="{{ route('admin.coupons.import.sample') }}" class="btn mt-2" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px 20px; font-weight: 600; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                تحميل ملف العينة
            </a>
        </div>
    </div>

    {{-- Upload Form --}}
    <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
        <div class="card-body" style="padding: 24px;">
            <form action="{{ route('admin.coupons.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="border: 2px dashed #d2d2d7; border-radius: 14px; padding: 48px 24px; text-align: center; transition: border-color 200ms ease, background 200ms ease;"
                     ondragover="event.preventDefault(); this.style.borderColor='#007aff'; this.style.background='#f0f7ff'"
                     ondragleave="this.style.borderColor='#d2d2d7'; this.style.background='transparent'"
                     ondrop="event.preventDefault(); this.style.borderColor='#007aff'; this.style.background='#f0f7ff'">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#86868b" stroke-width="1.5" style="margin-bottom: 12px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p style="font-size: 16px; font-weight: 600; color: #1d1d1f; margin-bottom: 4px;">اختر ملف CSV</p>
                    <p style="font-size: 13px; color: #86868b; margin-bottom: 16px;">الحد الأقصى: 5 ميجابايت</p>
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                        style="display: block; margin: 0 auto; font-size: 14px;"
                        onchange="document.getElementById('file-name').textContent = this.files[0]?.name || ''">
                    <p id="file-name" style="font-size: 13px; color: #007aff; margin-top: 8px; font-weight: 500;"></p>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn" style="background: #007aff; color: #fff; border-radius: 10px; padding: 12px 32px; font-weight: 600; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        معاينة الاستيراد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
