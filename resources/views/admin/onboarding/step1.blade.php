@extends('admin.onboarding.index')

@section('onboarding_content')
<div class="card" style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px;">
    <h2 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 4px;">
        <i class="fas fa-store" style="color: #6366f1;"></i> {{ __('Business Information') }}
    </h2>
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 24px;">
        {{ __('Tell us about your store. You can always change this later.') }}
    </p>

    <form method="POST" action="{{ route('admin.onboarding.saveStep1') }}">
        @csrf
        <div style="display: grid; gap: 20px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                    {{ __('Store Name') }} <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="companyName" value="{{ old('companyName', $business->companyName ?? '') }}"
                    style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; transition: border 0.2s;"
                    placeholder="{{ __('e.g. Al-Shifa Pharmacy') }}" required>
                @error('companyName') <p style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        {{ __('Phone Number') }}
                    </label>
                    <input type="text" name="phoneNumber" value="{{ old('phoneNumber', $business->phoneNumber ?? '') }}"
                        style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;"
                        placeholder="{{ __('+20 123 456 7890') }}">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                        {{ __('Address') }}
                    </label>
                    <input type="text" name="address" value="{{ old('address', $business->address ?? '') }}"
                        style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;"
                        placeholder="{{ __('City, Country') }}">
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 28px; gap: 12px;">
            <a href="{{ route('admin.onboarding.step', 2) }}" style="padding: 10px 20px; font-size: 14px; color: #6b7280; text-decoration: none; border: 1px solid #e5e7eb; border-radius: 8px;">
                {{ __('Skip') }}
            </a>
            <button type="submit" style="padding: 10px 24px; font-size: 14px; font-weight: 500; background: #6366f1; color: #fff; border: none; border-radius: 8px; cursor: pointer;">
                {{ __('Save & Continue') }} <i class="fas fa-arrow-right" style="margin-left: 6px;"></i>
            </button>
        </div>
    </form>
</div>
@endsection
