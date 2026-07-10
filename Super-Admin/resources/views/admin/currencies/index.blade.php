@extends('layouts.master')

@section('title')
    {{ __('Currency') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys ">
                    <div class="table-header p-16">
                        <h4>{{ __('Currency List') }}</h4>
                        @can('currencies-create')
                            <a href="{{ route('admin.currencies.create') }}" class="add-order-btn rounded-2"><i class="fas fa-plus-circle"></i> {{ __('Add Currency') }} </a>
                        @endcan
                    </div>

                    <div class="table-top-form p-16-0">
                        <form action="{{ route('admin.currencies.index') }}" method="GET" class="filter-form" table="#currencies-data">

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
                                    <input class="form-control searchInput" type="text" name="search" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
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

                <div id="currencies-data">
                    @include('admin.currencies.datas')
                </div>

            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include('admin.components.multi-delete-modal')
@endpush
