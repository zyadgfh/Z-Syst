@extends('layouts.master')

@section('title')
    {{ __('gateways.Manual Payment List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys ">
                    <div class="table-header p-16">
                        <h4>{{ __('gateways.Manual Payment List') }}</h4>
                    </div>

                    <div class="table-header justify-content-center border-0 text-center d-none d-block d-print-block">
                        <h4 class="mt-2">{{ __('gateways.Manual Payment List') }}</h4>
                    </div>

                    <div class="table-top-form sec-header d-print-none">
                        <form action="{{ route('admin.manual-payments.filter') }}" method="post" class="filter-form mb-0"
                            table="#manual-payment-data">
                            @csrf

                            <div class="table-top-left d-flex gap-3 ">
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="per_page" class="form-control">
                                        <option value="10">{{ __('common.Show- 10') }}</option>
                                        <option value="25">{{ __('common.Show- 25') }}</option>
                                        <option value="50">{{ __('common.Show- 50') }}</option>
                                        <option value="100">{{ __('common.Show- 100') }}</option>
                                    </select>
                                    <span></span>
                                </div>

                                <div class="table-search position-relative">
                                    <input class="form-control" type="text" name="search"
                                        placeholder="{{ __('common.Search...') }}" value="{{ request('search') }}">
                                    <span class="position-absolute">
                                        <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                    </span>
                                </div>
                            </div>
                        </form>

                        <div class="d-flex align-items-center gap-3">
                            <a href="{{ route('admin.manual-payments.csv') }}">
                                <img src="{{ asset('assets/images/icons/cvg.svg') }}" alt="user" id="">
                            </a>
                            <a href="{{ route('admin.manual-payments.excel') }}">
                                <img src="{{ asset('assets/images/icons/exel.svg') }}" alt="user" id="">
                            </a>

                            <a class="print-window">
                                <img src="{{ asset('assets/images/icons/print.svg') }}" alt="user" id="">
                            </a>
                        </div>

                    </div>
                </div>

                <div class="responsive-table table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="table-header-content">{{ __('common.SL') }}.</th>
                                <th class="table-header-content">{{ __('common.Date') }}</th>
                                <th class="table-header-content">{{ __('gateways.Shop Name') }}</th>
                                <th class="table-header-content">{{ __('common.Category') }}</th>
                                <th class="table-header-content">{{ __('common.Package') }}</th>
                                <th class="table-header-content">{{ __('gateways.Started') }}</th>
                                <th class="table-header-content">{{ __('gateways.End') }}</th>
                                <th class="table-header-content">{{ __('gateways.Gateway Method') }}</th>
                                <th class="table-header-content">{{ __('common.Status') }}</th>
                                <th class="table-header-content d-print-none">{{ __('common.Action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="manual-payment-data">
                            @include('admin.manual-payments.datas')
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $manual_payments->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

<div class="modal fade" id="approve-modal">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('common.Are you sure?') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <form action="" method="post" enctype="multipart/form-data"
                        class="ajaxform_instant_reload manualPaymentForm">
                        @csrf
                        <div class="row">
                            <div class="mt-0">
                                <label class="custom-top-label">{{ __('gateways.Enter Reason') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('gateways.Enter Reason') }}"></textarea>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="button-group text-center mt-5">
                                <a href="" class="theme-btn border-btn m-2">{{ __('common.Cancel') }}</a>
                                <button class="theme-btn m-2 submit-btn">{{ __('gateways.Accept') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reject-modal">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('gateways.Why are you reject it?') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <form action="" method="post" enctype="multipart/form-data"
                        class="ajaxform_instant_reload manualPaymentRejectForm">
                        @csrf
                        <div class="row">
                            <div class="mt-0">
                                <label class="custom-top-label">{{ __('gateways.Enter Reason') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('gateways.Enter Reason') }}"></textarea>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="button-group text-center mt-5">
                                <a href="" class="theme-btn border-btn m-2">{{ __('common.Cancel') }}</a>
                                <button class="theme-btn m-2 submit-btn">{{ __('common.Save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="manual-view-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('gateways.Subscriber View') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <div class="row mt-2">
                        <div class="col-12  costing-list">
                            <img width="100px" width="100px" class="rounded-circle border-2 shadow" src=""
                                id="image" alt="">
                        </div>
                    </div>
                    <div class="row align-items-center mt-4">
                        <div class="col-md-4">
                            <p>{{ __('common.Business Name') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p class="business_name"></p>
                        </div>
                    </div>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('common.Business Category') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="category"></p>
                        </div>
                    </div>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('common.Package') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="package"></p>
                        </div>
                    </div>
                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('gateways.Gateway Name') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="gateway"></p>
                        </div>
                    </div>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('gateways.Enroll Date') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="enroll_date"></p>
                        </div>
                    </div>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('gateways.Expire date') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="expired_date"></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
