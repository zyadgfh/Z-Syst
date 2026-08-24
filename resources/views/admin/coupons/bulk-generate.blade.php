@extends('layouts.master')

@section('title', 'إنشاء أكواد كوبونات بالجملة')

@section('main_content')
    <div class="container-fluid" style="padding: 24px; max-width: 820px;">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('admin.coupons.index') }}" style="color: #007aff; text-decoration: none; font-size: 14px; margin-left: 16px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 style="font-weight: 700; margin: 0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                    <line x1="9" y1="12" x2="15" y2="12"/>
                    <line x1="9" y1="16" x2="15" y2="16"/>
                </svg>
                إنشاء أكواد بالجملة
            </h4>
        </div>

        @if ($errors->any())
            <div class="alert" style="background: #fff0f0; color: #ff3b30; border-radius: 12px; padding: 12px 16px; border: none; margin-bottom: 16px;">
                <ul class="mb-0" style="list-style: none; padding: 0;">
                    @foreach ($errors->all() as $error)
                        <li style="font-size: 14px;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.coupons.bulk-generate.store') }}" method="POST" style="background: #fff; border-radius: 16px; border: 1px solid #e5e5ea; padding: 24px;">
            @csrf

            <div style="background: #f0f7ff; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; font-size: 13px; color: #0056b3; line-height: 1.6;">
                <strong>ملاحظة:</strong> سيتم إنشاء أكواد عشوائية فريدة بنفس إعدادات الخصم. يمكنك تخصيص البادئة وطول الكود أدناه.
            </div>

            <div class="row g-3">
                {{-- Count & Prefix --}}
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        عدد الأكواد <span style="color: #ff3b30;">*</span>
                    </label>
                    <input type="number" name="count" value="{{ old('count', 10) }}" required min="1" max="5000"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;"
                        placeholder="10">
                </div>
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        البادئة (اختياري)
                    </label>
                    <input type="text" name="prefix" value="{{ old('prefix') }}" maxlength="10"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding:10px 14px; font-size: 15px; text-transform: uppercase; letter-spacing: 0.04em;"
                        placeholder="مثال: CAMPAIGN">
                </div>
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        طول الكود
                    </label>
                    <input type="number" name="code_length" value="{{ old('code_length', 8) }}" min="6" max="20"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;"
                        placeholder="8">
                </div>

                <div class="col-12"><hr style="border: none; border-top: 1px solid #f0f0f2; margin: 8px 0;"></div>

                {{-- Discount Settings --}}
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        النوع <span style="color: #ff3b30;">*</span>
                    </label>
                    <select name="type" required style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                        <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        القيمة <span style="color: #ff3b30;">*</span>
                    </label>
                    <input type="number" name="value" value="{{ old('value') }}" required step="0.01" min="0.01"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;"
                        placeholder="20">
                </div>
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        الوصف
                    </label>
                    <input type="text" name="description" value="{{ old('description') }}"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;"
                        placeholder="حملة ترويجية">
                </div>

                {{-- Limits --}}
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        الحد الأدنى للطلب
                    </label>
                    <input type="number" name="minimum_order_amount" value="{{ old('minimum_order_amount', 0) }}" step="0.01" min="0"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        حد أقصى للخصم
                    </label>
                    <input type="number" name="maximum_discount_amount" value="{{ old('maximum_discount_amount') }}" step="0.01" min="0"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;"
                        placeholder="غير محدد">
                </div>
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        حد الاستخدام الكلي
                    </label>
                    <input type="number" name="usage_limit" value="{{ old('usage_limit', 1) }}" min="1"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>

                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        حد الاستخدام لكل مستخدم
                    </label>
                    <input type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', 1) }}" min="1"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>

                {{-- Dates --}}
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        تاريخ البداية
                    </label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-4">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">
                        تاريخ الانتهاء
                    </label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"
                        style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px;">
                        <input type="checkbox" name="single_use" value="1" {{ old('single_use', 1) ? 'checked' : '' }} style="width: 18px; height: 18px; border-radius: 4px;">
                        استخدام واحد لكل مستخدم
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn" style="background: #007aff; color: #fff; border-radius: 10px; padding: 10px 28px; font-weight: 600; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align: middle; margin-right: 4px;"><path d="M12 5v14M5 12h14"/></svg>
                    إنشاء الأكواد
                </button>
                <a href="{{ route('admin.coupons.index') }}" class="btn" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px 28px; font-weight: 600; text-decoration: none;">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
