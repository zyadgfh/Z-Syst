@extends('layouts.admin')

@section('title', 'إضافة كوبون جديد')

@section('main_content')
    <div class="container-fluid js-container-narrow">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('admin.coupons.index') }}" class="js-back-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="va-middle"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 class="heading-bold">كوبون جديد</h4>
        </div>

        @if ($errors->any())
            <div class="alert js-alert-error">
                <ul class="mb-0" class="list-unstyled">
                    @foreach ($errors->all() as $error)
                        <li class="fs-14">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.coupons.store') }}" method="POST" class="js-form-card">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label-sm">كود الكوبون <span class="text-danger-custom">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required class="input-uppercase-bold"
                        placeholder="مثال: SUMMER20">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">النوع <span class="text-danger-custom">*</span></label>
                    <select name="type" required class="form-input-clean">
                        <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label-sm">الوصف</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="form-input-clean" placeholder="خصم صيفي على جميع المنتجات">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">القيمة <span class="text-danger-custom">*</span></label>
                    <input type="number" name="value" value="{{ old('value') }}" required step="0.01" min="0.01"
                        class="form-input-clean" placeholder="20">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">الحد الأدنى للطلب</label>
                    <input type="number" name="minimum_order_amount" value="{{ old('minimum_order_amount', 0) }}" step="0.01" min="0"
                        class="form-input-clean" placeholder="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">حد أقصى للخصم</label>
                    <input type="number" name="maximum_discount_amount" value="{{ old('maximum_discount_amount') }}" step="0.01" min="0"
                        class="form-input-clean" placeholder="غير محدد">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">حد الاستخدام الكلي</label>
                    <input type="number" name="usage_limit" value="{{ old('usage_limit') }}" min="1"
                        class="form-input-clean" placeholder="غير محدد">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">حد الاستخدام لكل مستخدم</label>
                    <input type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', 1) }}" min="1"
                        class="form-input-clean">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">تاريخ البداية</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                        class="form-input-clean">
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">تاريخ الانتهاء</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"
                        class="form-input-clean">
                </div>
                <div class="col-md-12">
                    <label class="d-inline-flex cursor-pointer text-14">
                        <input type="checkbox" name="single_use" value="1" {{ old('single_use') ? 'checked' : '' }} class="checkbox-sm">
                        استخدام واحد لكل مستخدم
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn" class="btn-primary-blue" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    إنشاء الكوبون
                </button>
                <a href="{{ route('admin.coupons.index') }}" class="btn" class="btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
