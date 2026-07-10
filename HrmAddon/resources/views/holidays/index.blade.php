@extends('business::layouts.master')

@section('title')
    {{ __('Holiday List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Holiday List') }}</h4>
                        <a type="button" href="#holidays-create-modal" data-bs-toggle="modal"
                            class="add-order-btn rounded-2"><i class="fas fa-plus-circle me-1"></i>{{ __('Add new Holiday') }}</a>
                    </div>
                    <div class="table-top-form p-16-0">
                        <form action="{{ route('hrm.holidays.filter') }}" method="post" class="filter-form" table="#holidays-data">
                            @csrf
                            <div class="table-top-left d-flex gap-3 margin-l-16">
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
                                    <input type="text" name="search" class="form-control"
                                        placeholder="{{ __('Search...') }}">
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
                <div class="delete-item delete-show d-none">
                    <div class="delete-item-show">
                        <p class="fw-bold"><span class="selected-count"></span> {{ __('items show') }}</p>
                        <button data-bs-toggle="modal" class="trigger-modal" data-bs-target="#multi-delete-modal" data-url="{{ route('hrm.holidays.delete-all') }}">{{ __('Delete') }}</button>
                    </div>
                </div>
                <div class="responsive-table m-0">
                    <table class="table" id="datatable">
                        <thead>
                            <tr>
                                <th class="w-60">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="checkbox" class="select-all-delete  multi-delete">
                                    </div>
                                </th>
                                <th>{{ __('SL') }}.</th>
                                <th class="text-start">{{ __('Name') }}</th>
                                <th class="text-start">{{ __('Start Date') }}</th>
                                <th class="text-start">{{ __('End Date') }}</th>
                                <th class="text-start">{{ __('Description') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="holidays-data">
                            @include('hrmaddon::holidays.datas')
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $holidays->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include('hrmaddon::component.delete-modal')
    @include('hrmaddon::holidays.create')
    @include('hrmaddon::holidays.edit')
@endpush
