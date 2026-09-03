@extends('layouts.admin')

@section('title', 'إنشاء أكواد كوبونات بالجملة')

@section('main_content')
    <div class="container-fluid pad-24 max-w-820">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('admin.coupons.index') }}" class="c-blue no-underline fz14 ml-16">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="va-middle"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 class="heading-bold">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" class="icon-align-lg">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                    <line x1="9" y1="12" x2="15" y2="12"/>
                    <line x1="9" y1="16" x2="15" y2="16"/>
                </svg>
                إنشاء أكواد بالجملة
            </h4>
        </div>

        @if ($errors->any())
            <div class="alert alert-red">
                <ul class="mb-0" class="list-unstyled">
                    @foreach ($errors->all() as $error)
                        <li class="fs-14">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.coupons.bulk-generate.store') }}" method="POST" class="card-apple-lg">
            @csrf

            <div class="card-info-light">
                <strong>ملاحظة:</strong> سيتم إنشاء أكواد عشوائية فريدة بنفس إعدادات الخصم. يمكنك تخصيص البادئة وطول الكود أدناه.
            </div>

            <div class="row g-3">
                {{-- Count & Prefix --}}
                <div class="col-md-4">
                    <label class="form-label-sm">
                        عدد الأكواد <span class="text-danger-custom">*</span>
                    </label>
                    <input type="number" name="count" value="{{ old('count', 10) }}" required min="1" max="5000"
                        class="form-input-clean"
                        placeholder="10">
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">
                        البادئة (اختياري)
                    </label>
                    <input type="text" name="prefix" value="{{ old('prefix') }}" maxlength="10"
                        class="input-clean-uppercase"
                        placeholder="مثال: CAMPAIGN">
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">
                        طول الكود
                    </label>
                    <input type="number" name="code_length" value="{{ old('code_length', 8) }}" min="6" max="20"
                        class="form-input-clean"
                        placeholder="8">
                </div>

                <div class="col-12"><hr class="border-top-light my-8"></div>

                {{-- Discount Settings --}}
                <div class="col-md-4">
                    <label class="form-label-sm">
                        النوع <span class="text-danger-custom">*</span>
                    </label>
                    <select name="type" required class="form-input-clean">
                        <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">
                        القيمة <span class="text-danger-custom">*</span>
                    </label>
                    <input type="number" name="value" value="{{ old('value') }}" required step="0.01" min="0.01"
                        class="form-input-clean"
                        placeholder="20">
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">
                        الوصف
                    </label>
                    <input type="text" name="description" value="{{ old('description') }}"
                        class="form-input-clean"
                        placeholder="حملة ترويجية">
                </div>

                {{-- Limits --}}
                <div class="col-md-4">
                    <label class="form-label-sm">
                        الحد الأدنى للطلب
                    </label>
                    <input type="number" name="minimum_order_amount" value="{{ old('minimum_order_amount', 0) }}" step="0.01" min="0"
                        class="form-input-clean">
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">
                        حد أقصى للخصم
                    </label>
                    <input type="number" name="maximum_discount_amount" value="{{ old('maximum_discount_amount') }}" step="0.01" min="0"
                        class="form-input-clean"
                        placeholder="غير محدد">
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">
                        حد الاستخدام الكلي
                    </label>
                    <input type="number" name="usage_limit" value="{{ old('usage_limit', 1) }}" min="1"
                        class="form-input-clean">
                </div>

                <div class="col-md-4">
                    <label class="form-label-sm">
                        حد الاستخدام لكل مستخدم
                    </label>
                    <input type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', 1) }}" min="1"
                        class="form-input-clean">
                </div>

                {{-- Dates --}}
                <div class="col-md-4">
                    <label class="form-label-sm">
                        تاريخ البداية
                    </label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                        class="form-input-clean">
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">
                        تاريخ الانتهاء
                    </label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"
                        class="form-input-clean">
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <label class="d-inline-flex cursor-pointer text-14">
                        <input type="checkbox" name="single_use" value="1" {{ old('single_use', 1) ? 'checked' : '' }} class="checkbox-sm">
                        استخدام واحد لكل مستخدم
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn" class="btn-primary-blue" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="icon-align"><path d="M12 5v14M5 12h14"/></svg>
                    إنشاء الأكواد
                </button>
                <a href="{{ route('admin.coupons.index') }}" class="btn" class="btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
