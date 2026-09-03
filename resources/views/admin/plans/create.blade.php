@extends('layouts.master')

@section('title')
    {{ __('business.Add Subscription Plan') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card border-0">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{__('business.Add new Package')}}</h4>
                        @can('plans-read')
                            <a href="{{ route('admin.plans.index') }}" class="add-order-btn rounded-2 {{ Route::is('admin.users.create') ? 'active' : '' }}"><i class="far fa-list me-1" aria-hidden="true"></i> {{ __('business.Package List') }}</a>
                        @endcan
                    </div>
                    <div class="order-form-section  p-16">
                        <form action="{{ route('admin.plans.store') }}" method="POST" class="ajaxform_instant_reload">
                            @csrf
                            <div class="add-suplier-modal-wrapper d-block">
                                <div class="row">
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('business.Package Name') }}</label>
                                        <input type="text" name="subscriptionName" required class="form-control" placeholder="{{ __('business.Enter Package Name') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('business.Duration in Days') }}</label>
                                        <input type="number" step="any" name="duration" required class="form-control" placeholder="{{ __('business.Enter number') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('business.Offer Price') }}</label>
                                        <input type="number" step="any" name="offerPrice" class="form-control price" placeholder="{{ __('business.Enter Plan Price') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('business.Subscription Price') }}</label>
                                        <input type="number" step="any" name="subscriptionPrice" required class="form-control discount" placeholder="{{ __('business.Enter Subscription Price') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <div class="col-lg-12 my-2">
                                            <label>{{ __('common.Status') }}</label>
                                            <div class="form-control d-flex justify-content-between align-items-center radio-switcher">
                                                <p class="dynamic-text"></p>
                                                <label class="switch m-0">
                                                    <input type="checkbox" name="status" class="change-text" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mt-2">
                                        <label>{{ __('business.Add New Features') }}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control add-feature border-0 bg-transparent" placeholder="{{ __('business.Enter features') }}">
                                            <button class="feature-btn" id="feature-btn">{{ __('common.Save') }}</button>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="row feature-list">
                                            {{-- Will added dynamically. --}}
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="button-group text-center mt-5">
                                            <button type="reset" class="theme-btn border-btn m-2">{{ __('common.Cancel') }}</button>
                                            <button class="theme-btn m-2 submit-btn">{{ __('common.Save') }}</button>
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

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
