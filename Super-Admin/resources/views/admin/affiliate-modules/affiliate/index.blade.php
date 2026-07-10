@extends('layouts.master')

@section('title')
    {{ __('Affiliate List') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card ">
            <div class="card-bodys">
                <div class="table-header p-16">
                    <h4>{{ __('Affiliate List') }}</h4>
                </div>
                <div class="table-top-form p-16-0">
                    <form action="{{ route('admin.affiliates.filter') }}" method="post" class="filter-form" table="#affiliates-data">
                        @csrf

                        <div class="table-top-left d-flex gap-3 margin-l-16">
                            <div class="gpt-up-down-arrow position-relative">
                                <select name="per_page" class="form-control">
                                    <option @selected(request('per_page') == 20) value="20">{{ __('Show 20') }}</option>
                                    <option @selected(request('per_page') == 50) value="50">{{ __('Show 50') }}</option>
                                    <option @selected(request('per_page') == 100) value="100">{{ __('Show 100') }}</option>
                                    <option @selected(request('per_page') == 500) value="500">{{ __('Show 500') }}</option>
                                </select>
                                <span></span>
                            </div>

                            <div class="table-search position-relative">
                                <input class="form-control searchInput" type="text" name="search"
                                    placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                                <span class="position-absolute">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14.582 14.582L18.332 18.332" stroke="#4D4D4D" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M16.668 9.16797C16.668 5.02584 13.3101 1.66797 9.16797 1.66797C5.02584 1.66797 1.66797 5.02584 1.66797 9.16797C1.66797 13.3101 5.02584 16.668 9.16797 16.668C13.3101 16.668 16.668 13.3101 16.668 9.16797Z" stroke="#4D4D4D" stroke-width="1.25" stroke-linejoin="round"/>
                                        </svg>

                                </span>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

            <div class="responsive-table m-0">
                <table class="table" id="datatable">
                    <thead>
                    <tr>
                        <th>
                            <div class="d-flex align-items-center gap-1">
                                <label class="table-custom-checkbox">
                                    <input type="checkbox" class="table-hidden-checkbox selectAllCheckbox">
                                    <span class="table-custom-checkmark custom-checkmark"></span>
                                </label>
                                <i class="fal fa-trash-alt delete-selected"></i>
                            </div>
                        </th>
                        <th> {{ __('SL') }}. </th>
                        <th> {{ __('Date & Time') }} </th>
                        <th> {{ __('Name') }} </th>
                        <th> {{ __('Email') }} </th>
                        <th> {{ __('Subscription Plan') }} </th>
                        <th> {{ __('Duration') }} </th>
                        <th> {{ __('Expired Date') }} </th>
                        <th> {{ __('Total Earning') }} </th>
                        <th> {{ __('Status') }} </th>
                        <th> {{ __('Action') }} </th>
                    </tr>
                    </thead>
                    <tbody id="affiliates-data">
                        @include('admin.affiliate-modules.affiliate.datas')
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $affiliates->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('modal')
    @include('admin.components.multi-delete-modal')
    @include('admin.affiliate-modules.affiliate.view')
    @include('admin.affiliate-modules.affiliate.plan-upgrade')
@endpush

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
