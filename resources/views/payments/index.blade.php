@extends('layouts.web.blank')

@section('main_content')
<section class="payment-method-section pt-3 pt-5">
    <div class="container">
        <div class="payment-method-wrp">
            <div class="row">
                <div class="col-md-4">
                    <div class="nav d-block payment-method-nav">
                        <!-- Egyptian Payment Gateways -->
                        @if($companyGateways->isNotEmpty())
                            <h6 class="mb-2">{{ __('Egyptian Payment Gateways') }}</h6>
                            @foreach ($companyGateways as $gateway)
                            <a href="#egyptian-{{ $gateway->gateway_type }}" data-bs-toggle="pill" @class(['add-report-btn payment-items', 'active' => $loop->first ? true : false])>
                                {{ $gateway->gateway_type_label }}
                            </a>
                            @endforeach
                        @endif

                        <!-- Legacy Manual Gateways -->
                        @if($legacyGateways->isNotEmpty())
                            <h6 class="mb-2 mt-3">{{ __('Manual Payment') }}</h6>
                            @foreach ($legacyGateways as $gateway)
                            <a href="#manual-{{ str_replace(' ', '-', $gateway->name) }}" data-bs-toggle="pill" @class(['add-report-btn payment-items'])>
                                {{ ucfirst($gateway->name) }}
                            </a>
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="col-md-8 mt-3 mt-sm-0">
                    <div class="tab-content">
                        <!-- Egyptian Payment Gateways -->
                        @if($companyGateways->isNotEmpty())
                            @foreach ($companyGateways as $gateway)
                            <div @class(['tab-pane fade', 'show active' => $loop->first ? true : false]) id="egyptian-{{ $gateway->gateway_type }}">
                                <form action="{{ route('payments-gateways.payment', ['plan_id' => $plan->id, 'gateway_id' => $gateway->id]) }}" method="post" enctype="multipart/form-data">
                                    @csrf

                                    <div class="payment-list-table">
                                        @if ($errors->any())
                                            @foreach ($errors->all() as $error)
                                            <div class="alert alert-warning alert-dismissible fade show mt-2" role="alert">
                                                {{ $error }}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                            @endforeach
                                        @endif

                                        <h5 class="payment-title mb-3">{{ $gateway->gateway_type_label }}</h5>
                                        <table class="table table-striped">
                                            <tbody>
                                                <tr>
                                                    <td>{{ __('Payment Method') }}</td>
                                                    <td>{{ $gateway->gateway_type_label }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Subscription Name') }}</td>
                                                    <td>{{ $plan->subscriptionName }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Subscription Price') }}</td>
                                                    <td>{{ number_format($plan->offerPrice ?? $plan->subscriptionPrice, 2) }} EGP</td>
                                                </tr>
                                                @if($gateway->transaction_fee > 0)
                                                <tr>
                                                    <td>{{ __('Processing Fee') }}</td>
                                                    <td>
                                                        {{ $gateway->transaction_fee }}{{ $gateway->transaction_fee_type == 'percentage' ? '%' : ' EGP' }}
                                                    </td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <td>{{ __('Total Amount') }}</td>
                                                    <td>
                                                        {{ number_format(($plan->offerPrice ?? $plan->subscriptionPrice) + $gateway->calculateFee($plan->offerPrice ?? $plan->subscriptionPrice), 2) }} EGP
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!-- Phone Number (required for most Egyptian gateways) -->
                                        @if(in_array($gateway->gateway_type, ['vodafone_cash', 'fawry', 'orange_cash', 'instapay']))
                                        <div class="form-group mt-3">
                                            <label for="phone">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                            <input type="text" name="phone" id="phone" class="form-control" 
                                                   placeholder="{{ __('Enter your phone number') }}" required>
                                        </div>
                                        @endif

                                        <!-- Bank Card Details -->
                                        @if($gateway->gateway_type === 'bank_card')
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="card_number">{{ __('Card Number') }} <span class="text-danger">*</span></label>
                                                    <input type="text" name="card_number" id="card_number" class="form-control" 
                                                           placeholder="**** **** **** ****" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="card_holder">{{ __('Card Holder Name') }} <span class="text-danger">*</span></label>
                                                    <input type="text" name="card_holder" id="card_holder" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="expiry_month">{{ __('Expiry Month') }} <span class="text-danger">*</span></label>
                                                    <select name="expiry_month" id="expiry_month" class="form-control" required>
                                                        @for($i = 1; $i <= 12; $i++)
                                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="expiry_year">{{ __('Expiry Year') }} <span class="text-danger">*</span></label>
                                                    <select name="expiry_year" id="expiry_year" class="form-control" required>
                                                        @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                                                        <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="cvv">{{ __('CVV') }} <span class="text-danger">*</span></label>
                                                    <input type="text" name="cvv" id="cvv" class="form-control" 
                                                           placeholder="***" maxlength="4" required>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Cash Payment -->
                                        @if($gateway->gateway_type === 'cash')
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="received_amount">{{ __('Received Amount') }} <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" name="received_amount" id="received_amount" class="form-control" 
                                                           placeholder="{{ __('Enter cash amount') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="payment_notes">{{ __('Payment Notes') }}</label>
                                                    <textarea name="payment_notes" id="payment_notes" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-md payment-btn">{{ __('Pay Now') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @endforeach
                        @endif

                        <!-- Legacy Manual Gateways -->
                        @if($legacyGateways->isNotEmpty())
                            @foreach ($legacyGateways as $gateway)
                            <div class="tab-pane fade" id="manual-{{ str_replace(' ', '-', $gateway->name) }}">
                                <form action="{{ route('payments-gateways.payment', ['plan_id' => $plan->id, 'gateway_id' => $gateway->id]) }}" method="post" enctype="multipart/form-data">
                                    @csrf

                                    <div class="payment-list-table">
                                        <h5 class="payment-title mb-3">{{ ucfirst($gateway->name) }} ({{ optional($gateway->currency)->code }})</h5>
                                        <table class="table table-striped">
                                            <tbody>
                                                @php
                                                    $amount = convert_money($plan->offerPrice ?? $plan->subscriptionPrice, $gateway->currency);
                                                @endphp

                                                <tr>
                                                    <td>{{ __('Payment Method') }}</td>
                                                    <td>{{ $gateway->name }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Currency') }}</td>
                                                    <td>{{ optional($gateway->currency)->code }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Subscription Price') }}</td>
                                                    <td>{{ currency_format($amount, currency : $gateway->currency) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Processing Charge') }}</td>
                                                    <td>{{ currency_format($gateway->charge, currency: $gateway->currency) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('Total Amount') }}</td>
                                                    <td>{{ currency_format($amount + $gateway->charge, currency: $gateway->currency) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div class="row mb-3">
                                            <div class="col-12">
                                                @if ($gateway->accept_img)
                                                <div class="form-group">
                                                    <label for="">{{ __('Screenshot/Proof Image') }}</label>
                                                    <input type="file" name="attachment" class="form-control" required>
                                                </div>
                                                @endif
                                                @foreach ($gateway->manual_data['label'] ?? [] as $key => $row)
                                                <div class="form-group mt-3">
                                                    <label for="">{{ $row }}</label>
                                                    <input type="text" name="manual_data[]" @required($gateway->manual_data['is_required'][$key] == 1) class="form-control" placeholder="{{ __('Enter ').$row }}">
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-md payment-btn">{{ __('Submit Payment Request') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/css/payments.css') }}">
@endpush
