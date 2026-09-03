@extends('admin.onboarding.index')

@section('onboarding_content')
<div class="card" class="card-bordered-lg">
    <h2 class="section-title-hero">
        <i class="fas fa-pills" class="text-indigo"></i> {{ __('business.Add Your First Product') }}
    </h2>
    <p class="section-subtitle-md">
        {{ __('business.Add at least one product to get started. You can add more from the Products page.') }}
    </p>

    {{-- Existing products count --}}
    @if($products->count() > 0)
    <div class="alert-success-flex">
        <i class="fas fa-check-circle"></i>
        {{ __(':count product(s) added', ['count' => $products->count()]) }}
    </div>
    @endif

    <form method="POST" action="{{ route('admin.onboarding.saveStep3') }}">
        @csrf
        <div class="grid-gap-20">
            <div class="grid-2-1">
                <div>
                    <label class="form-label-md">
                        {{ __('products.Product Name') }} <span class="text-red">*</span>
                    </label>
                    <input type="text" name="productName" value="{{ old('productName') }}"
                        class="input-clean-alt"
                        placeholder="{{ __('business.e.g. Paracetamol 500mg') }}" required>
                    @error('productName') <p class="text-red-sm">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label-md">
                        {{ __('common.Category') }} <span class="text-red">*</span>
                    </label>
                    <select name="category_id" class="input-clean" required>
                        <option value="">{{ __('security.Select...') }}</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->categoryName }}
                        </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="text-red-sm">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid-2col">
                <div>
                    <label class="form-label-md">
                        {{ __('products.Selling Price') }} <span class="text-red">*</span>
                    </label>
                    <input type="number" name="sales_price" value="{{ old('sales_price') }}" step="0.01" min="0"
                        class="input-clean-alt"
                        placeholder="0.00" required>
                    @error('sales_price') <p class="text-red-sm">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label-md">
                        {{ __('common.Purchase Price') }}
                    </label>
                    <input type="number" name="purchase_without_tax" value="{{ old('purchase_without_tax') }}" step="0.01" min="0"
                        class="input-clean-alt"
                        placeholder="0.00">
                </div>
            </div>
        </div>

        <div class="flex-end-gap-12">
            <a href="{{ route('admin.onboarding.step', 2) }}" class="input-btn-outline">
                <i class="fas fa-arrow-left"></i> {{ __('common.Back') }}
            </a>
            <button type="submit" name="action" value="add" class="btn-success-solid">
                <i class="fas fa-plus"></i> {{ __('business.Add Product') }}
            </button>
            <button type="submit" name="action" value="continue" class="input-btn-primary">
                {{ __('common.Continue') }} <i class="fas fa-arrow-right" class="ml-6"></i>
            </button>
        </div>
    </form>
</div>
@endsection
