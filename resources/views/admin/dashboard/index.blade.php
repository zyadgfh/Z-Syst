@extends('layouts.master')

@section('title')
    {{ __('Dashboard') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="gpt-dashboard-card counter-grid-6 mt-30 mb-30">
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_businesses">0</h5>
                    <p>{{ __('Total Shop') }}</p>
                </div>
                <div class="icons">
                    <img src="{{ asset('assets/images/dashboard/stat1.svg') }}" alt="">
                </div>

            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="expired_businesses">0</h5>
                    <p>{{ __('Expired Businesses') }}</p>
                </div>
                <div class="icons">
                    <img src="{{ asset('assets/images/dashboard/stat2.svg') }}" alt="">
                </div>

            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="plan_subscribes">0</h5>
                    <p>{{ __('Plan Subscribes') }}</p>
                </div>
                <div class="icons">
                    <img src="{{ asset('assets/images/dashboard/stat3.svg') }}" alt="">
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="business_categories">0</h5>
                    <p>{{ __('Total Categories') }}</p>
                </div>
                <div class="icons">
                    <img src="{{ asset('assets/images/dashboard/stat4.svg') }}" alt="">
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_plans">0</h5>
                    <p>{{ __('Total Plans') }}</p>
                </div>
                <div class="icons">
                    <img src="{{ asset('assets/images/dashboard/stat5.svg') }}" alt="">
                </div>
            </div>

        </div>


        <div class="row gpt-dashboard-chart">

            <div class="col-xxl-4 mb-30">
                <div class="card new-card sms-report border-0 p-0 h-100">
                    <div class="chart-header">
                        <h4>{{ __('Subscription Plan') }}</h4>
                        <div class="gpt-up-down-arrow position-relative">
                            <select class="form-control overview-year">
                                @for ($i = date('Y'); $i >= 2022; $i--)
                                    <option @selected($i == date('Y')) value="{{ $i }}">{{ $i }}
                                    </option>
                                @endfor
                            </select>
                            <span></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="content">
                            <canvas id="plans-chart"  class="subscription-css"></canvas>
                        </div>

                        <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap subscription-plans subscription-label">
                            <!-- Plans will be dynamically inserted here -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-8 mb-30">
                <div class="card new-card dashboard-card border-0 p-0 h-100">
                    <div class="chart-header">
                        <h4>{{ __('Finance Overview') }}</h4>
                        <div class="gpt-up-down-arrow position-relative">
                            <select class="form-control yearly-statistics">
                                @for ($i = date('Y'); $i >= 2022; $i--)
                                    <option @selected($i == date('Y')) value="{{ $i }}">{{ $i }}
                                    </option>
                                @endfor
                            </select>
                            <span></span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex align-items-center justify-content-center gap-2 pt-2 pb-2">
                            <div class="green-circle"></div>
                            <p>{{ __('Total Subscription Amount:') }} <strong>$0</strong></p>
                        </div>

                        <div class="content">
                            <canvas id="monthly-statistics" class="chart-css"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="erp-table-section dashboard">
            <div class="card">
                <div class="card-bodys">
                    <div class="chart-header p-16 border-0">
                        <h4>{{ __('New Register user') }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.business.index') }}" class="view-btn">{{ __('View All') }} <i
                                    class="fas fa-arrow-right view-arrow"></i> </a>
                        </div>
                    </div>
                    <div class="erp-box-content ">
                        <div class="top-customer-table table-container mt-0">
                            <table class="table ">
                                <thead>
                                    <tr>
                                        <th class="table-header-content"> {{ __('SL') }}. </th>
                                        <th class="table-header-content">{{ __('Date & Time') }}</th>
                                        <th class="table-header-content">{{ __('Name') }}</th>
                                        <th class="table-header-content">{{ __('Category') }}</th>
                                        <th class="table-header-content">{{ __('Phone') }}</th>
                                        <th class="table-header-content">{{ __('Subscription Plan') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($businesses as $business)
                                        <tr class="table-content">
                                            <td class="table-single-content">
                                                {{ $loop->index + 1 }}
                                            </td>
                                            <td class="table-single-content">
                                                {{ formatted_date($business->created_at) }}
                                            </td>
                                            <td class="table-single-content">
                                                {{ $business->companyName }}
                                            </td>
                                            <td class="table-single-content">
                                                {{ $business->category->name }}
                                            </td>
                                            <td class="table-single-content">
                                                {{ $business->phoneNumber }}
                                            </td>
                                            <td class="table-single-content">
                                                <div class="d-flex align-items-center justify-content-center ">
                                                    <div class="
                                                    @if ($business->enrolled_plan?->plan?->subscriptionName === 'Free') free-bg
                                                    @elseif($business->enrolled_plan?->plan?->subscriptionName === 'Premium') premium-bg
                                                    @elseif($business->enrolled_plan?->plan?->subscriptionName === 'Standard') standard-bg @endif
                                                   ">
                                                        {{ $business->enrolled_plan?->plan?->subscriptionName }}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" value="{{ route('admin.dashboard.data') }}" id="get-dashboard">
    <input type="hidden" value="{{ route('admin.dashboard.plans-overview') }}" id="get-plans-overview">
    <input type="hidden" value="{{ route('admin.dashboard.subscriptions') }}" id="yearly-subscriptions-url">
@endsection

@push('js')
    <script src="{{ asset('assets/js/chart.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/dashboard.js') }}"></script>
@endpush
