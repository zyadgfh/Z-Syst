@extends('layouts.master')

@section('title')
    {{ __('Notifications List') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys ">
                    <div class="table-header p-16">
                        <h4>{{ __('Notifications List') }}</h4>
                    </div>
                    <div class="table-top-form p-16-0">
                    </div>
                </div>

                <div class="responsibe-table table-container">
                    <table class="table" id="erp-table">
                        <thead>
                            <tr>
                                <th class="table-header-content">@lang('SL.')</th>
                                <th class="table-header-content">@lang('Message')</th>
                                <th class="table-header-content">@lang('Created At')</th>
                                <th class="table-header-content">@lang('Read At')</th>
                                <th class="table-header-content">@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody id="notifications-data" class="searchResults">
                            @include('admin.notifications.datas')
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include('admin.components.multi-delete-modal')
@endpush
