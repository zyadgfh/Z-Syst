@extends('admin.onboarding.index')

@section('onboarding_content')
<div class="card" style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px;">
    <h2 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 4px;">
        <i class="fas fa-th-large" style="color: #6366f1;"></i> {{ __('Product Categories') }}
    </h2>
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 24px;">
        {{ __('Create categories to organize your products. Add at least one to continue.') }}
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
                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">
                    {{ __('New Category Name') }} <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" name="categoryName" value="{{ old('categoryName') }}"
                    style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none;"
                    placeholder="{{ __('e.g. Antibiotics, Vitamins, Supplements') }}" required>
                @error('categoryName') <p style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
            <button type="submit" name="action" value="add" style="padding: 10px 20px; font-size: 14px; font-weight: 500; background: #22c55e; color: #fff; border: none; border-radius: 8px; cursor: pointer; white-space: nowrap;">
                <i class="fas fa-plus"></i> {{ __('Add') }}
            </button>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 28px; gap: 12px;">
            <a href="{{ route('admin.onboarding.step', 1) }}" style="padding: 10px 20px; font-size: 14px; color: #6b7280; text-decoration: none; border: 1px solid #e5e7eb; border-radius: 8px;">
                <i class="fas fa-arrow-left"></i> {{ __('Back') }}
            </a>
            <button type="submit" name="action" value="continue" style="padding: 10px 24px; font-size: 14px; font-weight: 500; background: #6366f1; color: #fff; border: none; border-radius: 8px; cursor: pointer;">
                {{ __('Continue') }} <i class="fas fa-arrow-right" style="margin-left: 6px;"></i>
            </button>
        </div>
    </form>
</div>
@endsection
