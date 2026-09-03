@extends('layouts.admin')

@section('title')
    {{ __('Active Store List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys ">
                    <div class="table-header p-16">
                        <h4>{{ __('Active Store List') }}</h4>
                    </div>

                    <div class="table-header justify-content-center border-0 text-center d-none d-block d-print-block">
                        <h4 class="mt-2">{{ __('Active Store List') }}</h4>
                    </div>

                    <div class="table-top-form sec-header d-print-none">
                        <form action="{{ route('admin.active-stores.filter') }}" method="post" class="filter-form mb-0"
                            table="#active-stores-data">
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
                                    <input class="form-control" type="text" name="search"
                                        placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                                    <span class="position-absolute">
                                        <img src="{{ asset('assets/images/search.svg') }}" alt="">
                                    </span>
                                </div>
                            </div>
                        </form>

                        <div class="d-flex align-items-center gap-3">
                            <a href="{{ route('admin.active-stores.csv') }}">
                                <img src="{{ asset('assets/images/icons/cvg.svg') }}" alt="user" id="">
                            </a>
                            <a href="{{ route('admin.active-stores.excel') }}">
                                <img src="{{ asset('assets/images/icons/exel.svg') }}" alt="user" id="">
                            </a>

                            <a  class="print-window">
                                <img src="{{ asset('assets/images/icons/print.svg') }}" alt="user" id="">
                            </a>
                        </div>

                    </div>
                </div>

                <div class="responsive-table table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="table-header-content"> {{ __('SL') }}. </th>
                                <th class="table-header-content"> {{ __('Business Name') }} </th>
                                <th class="table-header-content"> {{ __('Business Category') }} </th>
                                <th class="table-header-content"> {{ __('Phone') }} </th>
                                <th class="table-header-content"> {{ __('Package') }} </th>
                                <th class="table-header-content"> {{ __('Last Enroll') }} </th>
                                <th class="table-header-content"> {{ __('Expired Date') }} </th>
                            </tr>
                        </thead>
                        <tbody id="active-stores-data">
                            @include('admin.reports.active-stores.datas')
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $active_stores->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
