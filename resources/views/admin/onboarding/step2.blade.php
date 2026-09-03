@extends('admin.onboarding.index')

@section('onboarding_content')
<div class="card" class="card-bordered-lg">
    <h2 class="section-title-hero">
        <i class="fas fa-th-large" class="text-indigo"></i> {{ __('business.Product Categories') }}
    </h2>
    <p class="section-subtitle-md">
        {{ __('business.Create categories to organize your products. Add at least one to continue.') }}
    </p>

    {{-- Existing categories --}}
    @if($categories->count() > 0)
    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
        @foreach($categories as $cat)
        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; border-radius: 20px; font-size: 13px; font-weight: 500;">
            <i class="fas fa-check-circle"></i> {{ $cat->categoryName }}
        </span>
        @endforeach
    </div>
    @endif

    {{-- Add category form --}}
    <form method="POST" action="{{ route('admin.onboarding.saveStep2') }}">
        @csrf
        <div style="display: flex; gap: 12px; align-items: flex-end;">
            <div style="flex: 1;">
                <label class="form-label-md">
                    {{ __('business.New Category Name') }} <span class="text-red">*</span>
                </label>
                <input type="text" name="categoryName" value="{{ old('categoryName') }}"
                    class="input-clean-alt"
                    placeholder="{{ __('business.e.g. Antibiotics, Vitamins, Supplements') }}" required>
                @error('categoryName') <p class="text-red-sm">{{ $message }}</p> @enderror
            </div>
            <button type="submit" name="action" value="add" style="padding: 10px 20px; font-size: 14px; font-weight: 500; background: #22c55e; color: #fff; border: none; border-radius: 8px; cursor: pointer; white-space: nowrap;">
                <i class="fas fa-plus"></i> {{ __('common.Add') }}
            </button>
        </div>

        <div class="flex-end-gap-12">
            <a href="{{ route('admin.onboarding.step', 1) }}" class="input-btn-outline">
                <i class="fas fa-arrow-left"></i> {{ __('common.Back') }}
            </a>
            <button type="submit" name="action" value="continue" class="input-btn-primary">
                {{ __('common.Continue') }} <i class="fas fa-arrow-right" class="ml-6"></i>
            </button>
        </div>
    </form>
</div>
@endsection
