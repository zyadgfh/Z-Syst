@extends('landing::layouts.master')

@section('title')
    {{ __('Edit Blog') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="table-header p-16">
                        <h4>{{ __('Edit Blog') }}</h4>
                        <a href="{{ route('admin.blogs.index') }}" class="theme-btn print-btn text-light">
                            <i class="fas fa-list me-1"></i>
                            {{ __('Blog List') }}
                        </a>
                    </div>

                    <div class="order-form-section p-16">
                        <form action="{{ route('admin.blogs.update', $blog->id) }}" method="post"
                            enctype="multipart/form-data" class="ajaxform_instant_reload">
                            @csrf
                            @method('PUT')

                            <div class="add-suplier-modal-wrapper">
                                <div class="row">
                                    <div class="col-lg-12 mt-2">
                                        <label>{{ __('Title') }}</label>
                                        <input type="text" name="title" value="{{ $blog->title }}" required
                                            class="form-control" placeholder="{{ __('Enter Title') }}">
                                    </div>
                                    <div class="col-lg-6 mt-2">
                                        <label>{{ __('Status') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="status" required="" class="form-control select-dropdown">
                                                <option value="1" @selected($blog->status == 1 ? 'active' : '')>
                                                    {{ __('Active') }}</option>
                                                <option value="0" @selected($blog->status == 0 ? 'active' : '')>
                                                    {{ __('Deactive') }}</option>
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mt-2">
                                        <div class="row">
                                            <div class="col-10">
                                                <label>{{ __('Image') }}</label>
                                                <input type="file" name="image" accept="image/*"
                                                    data-preview="#blog-image"
                                                    class="form-control">
                                            </div>
                                            <div class="col-2 align-self-center mt-3">
                                                <img src="{{ asset($blog->image ?? 'assets/img/demo-img.png') }}"
                                                    id="blog-image" class="table-img">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mb-2">
                                        <div class="row row-items">
                                            @foreach ($blog->tags ?? [] as $tag)
                                                <div class="col-sm-5">
                                                    <label for="">{{ __('Tags') }}</label>
                                                    <input type="text" name="tags[]" value="{{ $tag ?? '' }}"
                                                        class="form-control" required placeholder="{{ __('Enter tags name') }}">
                                                </div>
                                                <div class="col-sm-1 align-self-center mt-3">
                                                    <button type="button" class="btn text-danger trash remove-tag-btn"><i
                                                            class="fas fa-trash"></i></button>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mt-2">
                                                <a href="javascript:void(0)" class="fw-bold add-new-tag"><i
                                                        class="fas fa-plus-circle"></i>{{ __('Add new row') }}</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 mt-2">
                                        <label>{{ __('Description') }}</label>
                                        <textarea name="descriptions" class="form-control">{{ $blog->descriptions }}</textarea>
                                    </div>


                                    <h6 class="mt-5">{{ __('SEO Meta Tags') }}</h6>
                                    <div class="col-lg-12 mt-2">
                                        <label>{{ __('Meta Title') }}</label>
                                        <input type="text" name="meta[title]" value="{{ $blog->meta['title'] ?? '' }}"
                                            class="form-control" placeholder="{{ __('Enter Title') }}">
                                    </div>

                                    <div class="col-lg-12 mt-2">
                                        <label>{{ __('Meta Description') }}</label>
                                        <textarea type="text" name="meta[description]" class="form-control">{{ $blog->meta['description'] ?? '' }}</textarea>
                                    </div>


                                    <div class="col-lg-12">
                                        <div class="button-group text-center mt-5">
                                            <a href="" class="theme-btn border-btn m-2">{{ __('Cancel') }}</a>
                                            <button class="theme-btn m-2 submit-btn">{{ __('Update') }}</button>
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
