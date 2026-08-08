@extends('layouts.master')

@section('title')
    {{ __('General Settings') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="chart-header p-16 border-0">
                        <h4>{{ __('General Settings') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.update', $general->id) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            @method('put')
                            
                            <div class="row">
                                <div class="col-lg-12 mb-3">
                                    <label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" value="{{ $general->value['title'] ?? '' }}" required class="form-control" placeholder="{{ __('Enter Title') }}">
                                </div>
                                
                                <div class="col-lg-12 mb-3">
                                    <label class="form-label">{{ __('Copy Right') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="copy_right" value="{{ $general->value['copy_right'] ?? '' }}" required class="form-control" placeholder="{{ __('Enter Copyright Text') }}">
                                </div>

                                <div class="col-lg-12 mb-3">
                                    <label class="form-label">{{ __('Admin Footer Text') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="admin_footer_text" value="{{ $general->value['admin_footer_text'] ?? '' }}" required class="form-control" placeholder="{{ __('Enter Footer Text') }}">
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">{{ __('Admin Footer Link Text') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="admin_footer_link_text" value="{{ $general->value['admin_footer_link_text'] ?? '' }}" required class="form-control" placeholder="{{ __('Enter Link Text') }}">
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">{{ __('Admin Footer Link') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="admin_footer_link" value="{{ $general->value['admin_footer_link'] ?? '' }}" required class="form-control" placeholder="{{ __('Enter Link URL') }}">
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">{{ __('Admin Logo') }}</label>
                                    <div class="image-upload">
                                        <div class="upload-preview" id="admin-logo-preview">
                                            <img src="{{ asset($general->value['admin_logo'] ?? 'assets/images/logo/logo.png') }}" alt="Admin Logo" style="max-width: 200px; max-height: 100px; object-fit: contain;">
                                        </div>
                                        <input type="file" name="admin_logo" class="form-control" accept="image/*" id="admin-logo-input">
                                    </div>
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">{{ __('Favicon') }}</label>
                                    <div class="image-upload">
                                        <div class="upload-preview" id="favicon-preview">
                                            <img src="{{ asset($general->value['favicon'] ?? 'assets/images/logo/logo.png') }}" alt="Favicon" style="max-width: 64px; max-height: 64px; object-fit: contain;">
                                        </div>
                                        <input type="file" name="favicon" class="form-control" accept="image/*" id="favicon-input">
                                    </div>
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label class="form-label">{{ __('Frontend Logo') }}</label>
                                    <div class="image-upload">
                                        <div class="upload-preview" id="frontend-logo-preview">
                                            <img src="{{ asset($general->value['frontend_logo'] ?? 'assets/images/logo/logo.png') }}" alt="Frontend Logo" style="max-width: 200px; max-height: 100px; object-fit: contain;">
                                        </div>
                                        <input type="file" name="frontend_logo" class="form-control" accept="image/*" id="frontend-logo-input">
                                    </div>
                                </div>

                                @can('settings-update')
                                    <div class="col-lg-12">
                                        <div class="d-flex justify-content-end gap-2 mt-4">
                                            <a href="{{ route('admin.dashboard.index') }}" class="btn btn-secondary">
                                                {{ __('Cancel') }}
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>{{ __('Update Settings') }}
                                            </button>
                                        </div>
                                    </div>
                                @endcan
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
                // Image preview functionality
                const imageInputs = [
                    { input: 'admin-logo-input', preview: 'admin-logo-preview' },
                    { input: 'favicon-input', preview: 'favicon-preview' },
                    { input: 'frontend-logo-input', preview: 'frontend-logo-preview' }
                ];

                imageInputs.forEach(({ input, preview }) => {
                    const inputElement = document.getElementById(input);
                    const previewElement = document.getElementById(preview);
                    
                    if (inputElement && previewElement) {
                        inputElement.addEventListener('change', function(e) {
                            const file = e.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    previewElement.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; object-fit: contain;">`;
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
@endsection

