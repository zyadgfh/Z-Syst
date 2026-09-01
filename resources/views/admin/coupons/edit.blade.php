@extends('layouts.master')

@section('title', 'تعديل الكوبون: ' . $coupon->code)

@section('main_content')
    <div class="container-fluid" style="padding: 24px; max-width: 720px;">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('admin.coupons.index') }}" style="color: #007aff; text-decoration: none; font-size: 14px; margin-left: 16px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 style="font-weight: 700; margin: 0;">تعديل الكوبون: <code style="background: #f5f5f7; padding: 2px 8px; border-radius: 6px;">{{ $coupon->code }}</code></h4>
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

        <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" style="background: #fff; border-radius: 16px; border: 1px solid #e5e5ea; padding: 24px;">
            @csrf @method('PUT')

            <div class="row g-3">
                <div class="col-md-12">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">الوصف</label>
                    <input type="text" name="description" value="{{ old('description', $coupon->description) }}" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">النوع <span style="color: #ff3b30;">*</span></label>
                    <select name="type" required style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                        <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                        <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">القيمة <span style="color: #ff3b30;">*</span></label>
                    <input type="number" name="value" value="{{ old('value', $coupon->value) }}" required step="0.01" min="0.01" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">الحد الأدنى للطلب</label>
                    <input type="number" name="minimum_order_amount" value="{{ old('minimum_order_amount', $coupon->minimum_order_amount) }}" step="0.01" min="0" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">حد أقصى للخصم</label>
                    <input type="number" name="maximum_discount_amount" value="{{ old('maximum_discount_amount', $coupon->maximum_discount_amount) }}" step="0.01" min="0" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">حد الاستخدام الكلي</label>
                    <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">حد الاستخدام لكل مستخدم</label>
                    <input type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" min="1" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">تاريخ البداية</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">تاريخ الانتهاء</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">الحالة</label>
                    <select name="active" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                        <option value="1" {{ old('active', $coupon->active) ? 'selected' : '' }}>نشط</option>
                        <option value="0" {{ !old('active', $coupon->active) ? 'selected' : '' }}>معطّل</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; margin-top: 28px;">
                        <input type="checkbox" name="single_use" value="1" {{ old('single_use', $coupon->single_use) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                        استخدام واحد لكل مستخدم
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn" style="background: #007aff; color: #fff; border-radius: 10px; padding: 10px 28px; font-weight: 600;">تحديث</button>
                <a href="{{ route('admin.coupons.index') }}" class="btn" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px 28px; font-weight: 600; text-decoration: none;">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
