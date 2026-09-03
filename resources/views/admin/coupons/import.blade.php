@extends('layouts.admin')

@section('title', 'استيراد كوبونات بالجملة')

@section('main_content')
<div class="container-fluid pad-24 max-w-800">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.coupons.index') }}" class="c-blue no-underline ml-16">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <h4 class="heading-bold">استيراد كوبونات من ملف CSV</h4>
    </div>

    @if ($errors->any())
        <div class="alert-red-lg">
            <ul class="mb-0" class="list-unstyled">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Instructions --}}
    <div class="card mb-4" class="card-clean-bordered">
        <div class="card-body" class="card-body">
            <h6 class="fw-600-mb12">الخطوات:</h6>
            <ol class="fz14-text-list">
                <li>حمّل ملف العينة للإطلاع على التنسيق المطلوب</li>
                <li>املأ الملف بالبيانات (الكود والنوع والقيمة مطلوبة)</li>
                <li>ارفع الملف هنا للمعاينة</li>
                <li>راجع النتائج ثم اضغط "تأكيد الاستيراد"</li>
            </ol>

            <a href="{{ route('admin.coupons.import.sample') }}" class="btn mt-2 btn-apple-gray" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                تحميل ملف العينة
            </a>
        </div>
    </div>

    {{-- Upload Form --}}
    <div class="card" class="card-clean-bordered">
        <div class="card-body" class="card-body-lg">
            <form action="{{ route('admin.coupons.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-dropzone"
                     ondragover="event.preventDefault(); this.style.borderColor='#007aff'; this.style.background='#f0f7ff'"
                     ondragleave="this.style.borderColor='#d2d2d7'; this.style.background='transparent'"
                     ondrop="event.preventDefault(); this.style.borderColor='#007aff'; this.style.background='#f0f7ff'">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#86868b" stroke-width="1.5" class="mb-12">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p class="fz16-semibold-mb4">اختر ملف CSV</p>
                    <p class="fz13-muted-mb16">الحد الأقصى: 5 ميجابايت</p>
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="block-center fz14"
                        onchange="document.getElementById('file-name').textContent = this.files[0]?.name || ''">
                    <p id="file-name" class="fz13-blue-mt8"></p>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-apple-blue-xl" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-align"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        معاينة الاستيراد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
