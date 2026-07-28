@extends('landing::layouts.master')

@section('title')
    {{ __('Messages List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-bodys ">
                    <div class="table-header p-16">
                        <h4>{{ __('Messages List') }}</h4>
                    </div>

                    <div class="table-header justify-content-center border-0 text-center d-none d-block d-print-block">
                        <h4 class="mt-2">{{ __('Messages List') }}</h4>
                    </div>

                    <div class="table-top-form sec-header d-print-none">
                        <form action="{{ route('admin.messages.filter') }}" method="post" class="filter-form"
                            table="#messages-data">
                            @csrf

                            <div class="table-top-left d-flex gap-3 ">
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="per_page" class="form-control">
                                        <option value="10">{{ __('Show- 10') }}</option>
                                        <option value="25">{{ __('Show- 25') }}</option>
                                        <option value="50">{{ __('Show- 50') }}</option>
                                        <option value="100">{{ __('Show- 100') }}</option>
                                    </select>
                                    <span></span>
                                </div>

                                <div class="table-search position-relative">
                                    <input class="form-control searchInput" type="text" name="search"
                                        placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                                    <span class="position-absolute">
                                        <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                    </span>
                                </div>
                            </div>
                        </form>

                        <div class="d-flex align-items-center gap-3 d-print-none margin-top-print">
                            <a href="{{ route('admin.messages.csv') }}">
                                <img src="{{ asset('assets/images/icons/cvg.svg') }}" alt="user" id="">
                            </a>
                            <a href="{{ route('admin.messages.excel') }}">
                                <img src="{{ asset('assets/images/icons/exel.svg') }}" alt="user" id="">
                            </a>
                            <a  class="print-window">
                                <img src="{{ asset('assets/images/icons/print.svg') }}" alt="user" id="">
                            </a>
                        </div>

                    </div>
                </div>

                <div class="delete-item delete-show d-none multi-delete-container">
                    <div class="delete-item-show d-flex align-items-center justify-content-between w-100">
                        <p class="fw-bold"><span class="selected-count"></span> {{ __('items selected') }}</p>
                        <button data-bs-toggle="modal" class="trigger-modal" data-bs-target="#multi-delete-modal" data-url="{{ route('admin.messages.delete-all') }}">{{ __('Delete') }}</button>
                    </div>
                </div>

                <div class="responsive-table table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="table-header-content d-print-none">
                                    <div class="d-flex align-items-center gap-1">
                                        <label class="table-custom-checkbox">
                                            <input type="checkbox"
                                                class="table-hidden-checkbox selectAllCheckbox select-all-delete multi-delete">
                                            <span class="table-custom-checkmark custom-checkmark"></span>
                                        </label>
                                    </div>
                                </th>

                                <th class="table-header-content">{{ __('SL') }}.</th>
                                <th class="table-header-content">{{ __('Name') }}</th>
                                <th class="table-header-content">{{ __('Phone') }}</th>
                                <th class="table-header-content">{{ __('Email') }}</th>
                                <th class="table-header-content">{{ __('Company Name') }}</th>
                                <th class="table-header-content">{{ __('Message') }}</th>
                                <th class="table-header-content d-print-none">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="messages-data">
                            @include('landing::admin.messages.datas')
                        </tbody>
                    </table>
                </div>
                <div>
                    {{ $messages->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection


@push('modal')
    @include('admin.components.multi-delete-modal')
@endpush
