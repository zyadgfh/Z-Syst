@extends('admin.onboarding.index')

@section('onboarding_content')
<div class="card" class="card-bordered-lg">
    <h2 class="section-title-hero">
        <i class="fas fa-store" class="text-indigo"></i> {{ __('business.Business Information') }}
    </h2>
    <p class="section-subtitle-md">
        {{ __('business.Tell us about your store. You can always change this later.') }}
    </p>

    <form method="POST" action="{{ route('admin.onboarding.saveStep1') }}">
        @csrf
        <div style="display: grid; gap: 20px;">
            <div>
                <label class="form-label-md">
                    {{ __('business.Store Name') }} <span class="text-red">*</span>
                </label>
                <input type="text" name="companyName" value="{{ old('companyName', $business->companyName ?? '') }}"
                    style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; transition: border 0.2s;"
                    placeholder="{{ __('business.e.g. Al-Shifa Pharmacy') }}" required>
                @error('companyName') <p class="text-red-sm">{{ $message }}</p> @enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label class="form-label-md">
                        {{ __('common.Phone Number') }}
                    </label>
                    <input type="text" name="phoneNumber" value="{{ old('phoneNumber', $business->phoneNumber ?? '') }}"
                        class="input-clean-alt"
                        placeholder="{{ __('business.+20 123 456 7890') }}">
                </div>
                <div>
                    <label class="form-label-md">
                        {{ __('common.Address') }}
                    </label>
                    <input type="text" name="address" value="{{ old('address', $business->address ?? '') }}"
                        class="input-clean-alt"
                        placeholder="{{ __('business.City, Country') }}">
                </div>
            </div>
        </div>

        <div class="flex-end-gap-12">
            <a href="{{ route('admin.onboarding.step', 2) }}" class="input-btn-outline">
                {{ __('common.Skip') }}
            </a>
            <button type="submit" class="input-btn-primary">
                {{ __('business.Save & Continue') }} <i class="fas fa-arrow-right" class="ml-6"></i>
            </button>
        </div>
    </form>
</div>
@endsection
