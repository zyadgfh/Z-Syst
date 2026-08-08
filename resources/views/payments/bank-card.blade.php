@extends('layouts.web.blank')

@section('main_content')
<section class="payment-method-section pt-3 pt-5">
    <div class="container">
        <div class="payment-method-wrp">
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h4 class="payment-title mb-4">{{ __('Bank Card Payment') }}</h4>
                            
                            <div class="alert alert-info">
                                <h5>{{ __('Payment Instructions') }}</h5>
                                <p>{{ __('Please transfer the amount to the following bank account:') }}</p>
                                <p><strong>{{ $info['bank_name'] }}</strong></p>
                                <p>{{ __('Account Number') }}: <strong>{{ $info['account_number'] }}</strong></p>
                                <p>{{ __('Account Name') }}: <strong>{{ $info['merchant_name'] }}</strong></p>
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

                            <form action="{{ route('bank-card.status') }}" method="post">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="transaction_id">{{ __('Transaction ID') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" required placeholder="{{ __('Enter your transaction ID') }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="card_last_four">{{ __('Card Last 4 Digits') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="card_last_four" id="card_last_four" class="form-control" required placeholder="{{ __('Enter last 4 digits of your card') }}" maxlength="4">
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