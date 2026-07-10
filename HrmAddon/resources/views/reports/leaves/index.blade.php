@extends('business::layouts.master')

@section('title')
    {{ __('Leave Report') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Leave Report') }}</h4>
                      </div>
                    <div class="table-top-form p-16-0">
                        <div  class="d-flex align-items-center gap-1 flex-wrap">
                            <form action="{{ route('hrm.leave-reports.filter') }}" method="post" class="filter-form" table="#leaves-report-data">
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
                            <form action="{{ route('hrm.leave-reports.filter') }}" method="post" class="filter-form" table="#leaves-report-data">
                                @csrf
                                <div class="table-top-left d-flex gap-3 margin-l-16">
                                     <div class="gpt-up-down-arrow position-relative d-print-none custom-date-filter">
                                        <select name="employee" class="form-control">
                                            <option value="">{{ __('Select one') }}</option>
                                            @foreach ($employees as $employee)
                                                <option>{{ $employee->name }}</option>
                                            @endforeach
                                        </select>
                                        <span></span>
                                    </div>
                                    <div class="gpt-up-down-arrow position-relative d-print-none custom-date-filter">
                                        <select name="month" class="form-control">
                                            @for ($month = 1; $month <= 12; $month++)
                                                <option {{ $month == date('m') ? 'selected' : '' }} value="{{ strtolower(date('F', mktime(0, 0, 0, $month, 1))) }}">
                                                    {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                                                </option>
                                            @endfor
                                        </select>
                                        <span></span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="responsive-table m-0">
                    <table class="table" id="datatable">
                        <thead>
                            <tr>
                                <th>{{ __('SL') }}.</th>
                                <th class="text-start">{{ __('Employee') }}</th>
                                <th class="text-start">{{ __('Month') }}</th>
                                <th class="text-start">{{ __('Department') }}</th>
                                <th class="text-start">{{ __('Start Date') }}</th>
                                <th class="text-start">{{ __('End Date') }}</th>
                                <th class="text-start">{{ __('Leave Type') }}</th>
                                <th class="text-start">{{ __('Leave Duration') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody id="leaves-report-data">
                            @include('hrmaddon::reports.leaves.datas')
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $leaves->links('vendor.pagination.bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection


