@extends('layouts.web.blank')

@section('main_content')
<section class="payment-method-section pt-3 pt-5">
    <div class="container">
        <div class="payment-method-wrp">
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h4 class="payment-title mb-4">{{ __('Fawry Payment') }}</h4>
                            
                            <div class="alert alert-info">
                                <h5>{{ __('Payment Instructions') }}</h5>
                                <p>{{ __('You can pay using Fawry at any of their locations or through their app.') }}</p>
                                <p>{{ __('Merchant Code') }}: <strong>{{ $info['merchant_code'] }}</strong></p>
                                <p>{{ __('Merchant Name') }}: <strong>{{ $info['merchant_name'] }}</strong></p>
                            </div>

                            <div class="payment-details mb-4">
                                <table class="table table-bordered">
                                    <tr>
                                        <td>{{ __('Amount to Pay') }}</td>
                                        <td><strong>{{ currency_format($info['amount'], $gateway->currency) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Processing Charge') }}</td>
                                        <td>{{ currency_format($info['charge'], $gateway->currency) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Total Amount') }}</td>
                                        <td><strong>{{ currency_format($info['amount'] + $info['charge'], $gateway->currency) }}</strong></td>
                                    </tr>
                                </table>
                            </div>

                            <form action="{{ route('fawry.status') }}" method="post">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="transaction_id">{{ __('Transaction ID') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" required placeholder="{{ __('Enter your transaction ID') }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="ref_number">{{ __('Fawry Reference Number') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="ref_number" id="ref_number" class="form-control" required placeholder="{{ __('Enter Fawry reference number') }}">
                                </div>
                                <button type="submit" class="btn btn-success">{{ __('Confirm Payment') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection