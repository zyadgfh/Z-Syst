@extends('layouts.master')

@section('title')
    {{ __('Add Product') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="chart-header p-16 border-0">
                        <h4>{{ __('Add New Product') }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="product-form">
                            @csrf
                            
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <h5 class="mb-3">{{ __('Basic Information') }}</h5>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('Product Name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="name" required value="{{ old('name') }}">
                                                @error('name')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('Generic Name') }}</label>
                                                <input type="text" class="form-control" name="generic_name" value="{{ old('generic_name') }}">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('Barcode') }}</label>
                                                <input type="text" class="form-control" name="barcode" value="{{ old('barcode') }}">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('SKU') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="sku" required value="{{ old('sku') }}">
                                                @error('sku')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                                                <select class="form-select" name="category_id" required>
                                                    <option value="">{{ __('Select Category') }}</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('category_id')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{ __('Manufacturer') }}</label>
                                                <select class="form-select" name="manufacturer_id">
                                                    <option value="">{{ __('Select Manufacturer') }}</option>
                                                    @foreach ($manufacturers as $manufacturer)
                                                        <option value="{{ $manufacturer->id }}" {{ old('manufacturer_id') == $manufacturer->id ? 'selected' : '' }}>
                                                            {{ $manufacturer->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pricing -->
                                    <div class="mb-4">
                                        <h5 class="mb-3">{{ __('Pricing') }}</h5>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('Purchase Price') }} <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" name="purchase_price" required value="{{ old('purchase_price') }}">
                                                @error('purchase_price')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('Selling Price') }} <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" name="selling_price" required value="{{ old('selling_price') }}">
                                                @error('selling_price')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('Tax (%)') }}</label>
                                                <input type="number" step="0.01" class="form-control" name="tax_rate" value="{{ old('tax_rate', 0) }}">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stock Information -->
                                    <div class="mb-4">
                                        <h5 class="mb-3">{{ __('Stock Information') }}</h5>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('Reorder Level') }}</label>
                                                <input type="number" class="form-control" name="reorder_level" value="{{ old('reorder_level', 10) }}">
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('Unit') }}</label>
                                                <select class="form-select" name="unit">
                                                    <option value="pcs" {{ old('unit') == 'pcs' ? 'selected' : '' }}>{{ __('Pieces') }}</option>
                                                    <option value="box" {{ old('unit') == 'box' ? 'selected' : '' }}>{{ __('Box') }}</option>
                                                    <option value="bottle" {{ old('unit') == 'bottle' ? 'selected' : '' }}>{{ __('Bottle') }}</option>
                                                    <option value="strip" {{ old('unit') == 'strip' ? 'selected' : '' }}>{{ __('Strip') }}</option>
                                                    <option value="pack" {{ old('unit') == 'pack' ? 'selected' : '' }}>{{ __('Pack') }}</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('Storage Location') }}</label>
                                                <input type="text" class="form-control" name="storage_location" value="{{ old('storage_location') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Side Panel -->
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <h5 class="mb-3">{{ __('Product Image') }}</h5>
                                        <div class="image-upload">
                                            <div class="upload-preview" id="image-preview">
                                                <div class="upload-placeholder">
                                                    <i class="fas fa-cloud-upload-alt fa-3x"></i>
                                                    <p>{{ __('Click or drag image here') }}</p>
                                                </div>
                                            </div>
                                            <input type="file" class="form-control" name="image" id="image-input" accept="image/*">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h5 class="mb-3">{{ __('Status') }}</h5>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                            <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h5 class="mb-3">{{ __('Description') }}</h5>
                                        <textarea class="form-control" name="description" rows="4">{{ old('description') }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>{{ __('Save Product') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Image preview
                const imageInput = document.getElementById('image-input');
                const imagePreview = document.getElementById('image-preview');
                
                if (imageInput && imagePreview) {
                    imageInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                imagePreview.innerHTML = `
                                    <img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; object-fit: contain;">
                                    <button type="button" class="btn btn-sm btn-danger remove-image" style="position: absolute; top: 5px; right: 5px;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                                
                                imagePreview.querySelector('.remove-image').addEventListener('click', function() {
                                    imageInput.value = '';
                                    imagePreview.innerHTML = `
                                        <div class="upload-placeholder">
                                            <i class="fas fa-cloud-upload-alt fa-3x"></i>
                                            <p>{{ __('Click or drag image here') }}</p>
                                        </div>
                                    `;
                                });
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
