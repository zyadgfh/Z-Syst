@extends('landing::layouts.master')

@section('title')
    {{ __('Create Blog') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="table-header p-16">
                        <h4>{{ __('Create Blog') }}</h4>
                        <a href="{{ route('admin.blogs.index') }}" class="theme-btn print-btn text-light">
                            <i class="fas fa-list me-1"></i>
                            {{ __('Blog List') }}
                        </a>
                    </div>
                    <div class="order-form-section p-16">
                        <form action="{{ route('admin.blogs.store') }}" method="post" enctype="multipart/form-data"
                            class="ajaxform_instant_reload">
                            @csrf
                            <div class="add-suplier-modal-wrapper">
                                <div class="row">
                                    <div class="col-lg-12 mt-2">
                                        <label>{{ __('Title') }}</label>
                                        <input type="text" name="title" required class="form-control"
                                            placeholder="{{ __('Enter Title') }}">
                                    </div>

                                    <div class="col-lg-6 mt-2">
                                        <label>{{ __('Status') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="status" required="" class="form-control select-dropdown">
                                                <option value="">{{ __('Select a status') }}</option>
                                                <option value="1">{{ __('Active') }}</option>
                                                <option value="0">{{ __('Deactive') }}</option>
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-5 mt-2">
                                        <div>
                                            <label>{{ __('Image') }}</label>
                                            <input type="file" accept="image/*" name="image"
                                                class="form-control file-input-change" data-id="image" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-1 mt-2">
                                        <div class="align-self-center img-align mt-3">
                                            <img src="{{ asset('assets/images/icons/upload.png') }}" id="image"
                                                class="table-img">
                                        </div>
                                    </div>

                                    <div class="col-lg-12 mt-2">
                                        <label>{{ __('Description') }}</label>
                                        <textarea type="text" name="descriptions" placeholder="{{ __('Enter Description') }}" class="form-control"></textarea>
                                    </div>

                                    <div class="col-12 mb-2">
                                        <div class="row row-items">
                                            <div class="col-sm-5">
                                                <label for="">{{ __('Tags') }}</label>
                                                <input type="text" name="tags[]" class="form-control" required
                                                    placeholder="{{ __('Enter tags name') }}">
                                            </div>
                                            <div class="col-sm-1 align-self-center mt-3">
                                                <button type="button" class="btn text-danger trash remove-tag-btn">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                        </div>

                                        <div class="row">
                                            <div class="col-12 mt-2">
                                                <a href="javascript:void(0)" class="fw-bold text-primary add-new-tag">
                                                    <i class="fas fa-plus-circle"></i> {{ __('Add new row') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>


                                    <h6 class="mt-5">{{ __('SEO Meta Tags') }}</h6>

                                    <div class="col-lg-12 mt-2">
                                        <label>{{ __('Meta Title') }}</label>
                                        <input type="text" name="meta[title]" class="form-control"
                                            placeholder="{{ __('Enter Title') }}">
                                    </div>

                                    <div class="col-lg-12 mt-2">
                                        <label>{{ __('Meta Description') }}</label>
                                        <textarea type="text" name="meta[description]" placeholder="{{ __('Enter Description') }}" class="form-control"></textarea>
                                    </div>


                                    <div class="col-lg-12">
                                        <div class="button-group text-center mt-5">
                                            <a href="" class="theme-btn border-btn m-2">{{ __('Cancel') }}</a>
                                            <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
