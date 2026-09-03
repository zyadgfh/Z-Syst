@extends('layouts.blank')

@section('title')
    {{ __('gateways.Manual Payment Report') }}
@endsection

@section('main_content')
    <div class="invoice-container">
        <div class="invoice-content">

            <div class="py-2 d-flex align-items-start justify-content-between border-bottom print-container">

                <div class="d-flex align-items-center p-2 table-header border-0 d-print-none">
                    <h4 class="Money-Receipt ms-2">{{ __('gateways.Manual Payment Report') }}</h4>
                </div>


                <div class="d-flex justify-content-end align-items-end d-print-none">
                    <div class="d-flex gap-3">
                        <a class="print-btn-2 print-btn-invoice print-window" ><img class="w-10 h-10" src="{{ asset('assets/img/print.svg') }}">{{ __('common.Print') }}</a>
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-between align-items-center gap-3 print-logo-container">
                <div class="d-flex align-items-center gap-2 logo">
                    <div>
                        <h3 class="mb-0">{{ $manual_payment->business?->companyName ?? '' }}</h3>
                    </div>
                </div>
                <h3 class="right-invoice mb-0 align-self-center">{{ __('gateways.INVOICE') }}</h3>
            </div>


            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <table class="table">
                        <tbody>
                        <tr class="in-table-row">
                            <td class="text-start">{{ __('gateways.Bill To') }}</td>
                            <td class="text-start">: {{ $manual_payment->business?->companyName ?? '' }}</td>
                        </tr>
                        <tr class="in-table-row">
                            <td class="text-start">{{ __('gateways.Mobile') }}</td>
                            <td class="text-start">:  {{ $manual_payment->business?->phoneNumber ?? '' }} </td>
                        </tr>
                        <tr class="in-table-row">
                            <td class="text-start">{{ __('common.Address') }}</td>
                            <td class="text-start">: {{ $manual_payment->business?->address ?? '' }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div>
                <table class="table table-striped">
                    <thead>
                        <tr class="in-table-header">
                            <th class="head-red text-center">{{ __('common.SL') }}</th>
                            <th class="head-red text-center">{{ __('common.Business Name') }}</th>
                            <th class="head-black text-center">{{ __('gateways.Plan') }}</th>
                            <th class="head-black text-center">{{ __('gateways.Started') }}</th>
                            <th class="head-black text-center">{{ __('gateways.End') }}</th>
                            <th class="head-black text-center">{{ __('gateways.Gateway Name') }}</th>
                        </tr>
                    </thead>

                    <tbody class="in-table-body-container">


                        <tr class="in-table-body">
                            <td class="text-center">1</td>
                            <td class="text-center">{{ $manual_payment->business->companyName ?? 'N/A' }}</td>
                            <td class="text-center">{{ $manual_payment->plan->subscriptionName ?? 'N/A' }}</td>
                            <td class="text-center">{{ formatted_date($manual_payment->created_at) }}</td>
                            <td class="text-center">{{ $manual_payment->created_at ? formatted_date($manual_payment->created_at->addDays($manual_payment->duration)) : '' }}</td>
                            <td class="text-center">{{ $manual_payment->gateway->name ?? 'N/A' }}</td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between position-relative">
                <div>
                    <table class="table">
                        <tbody>
                            <tr class="in-table-row">
                                <td class="text-start"></td>
                            </tr>
                            <tr class="in-table-row">
                                <td class="text-start"></td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
