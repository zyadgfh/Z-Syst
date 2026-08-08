@extends('layouts.web.blank')

@section('main_content')
<section class="payment-method-section pt-3 pt-5">
    <div class="container">
        <div class="payment-method-wrp">
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h4 class="payment-title mb-4">{{ __('Cash Payment') }}</h4>
                            
                            <div class="alert alert-info">
                                <h5>{{ __('Payment Instructions') }}</h5>
                                <p>{{ __('Please visit one of our branches to pay in cash.') }}</p>
                                <p>{{ __('Branch Name') }}: <strong>{{ $info['branch_name'] }}</strong></p>
                                <p>{{ __('Contact Person') }}: <strong>{{ $info['merchant_name'] }}</strong></p>
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

                            <form action="{{ route('cash-payment.status') }}" method="post">
                                @csrf
                                <div class="form-group mb-3">
                                    <label for="transaction_id">{{ __('Receipt Number') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" required placeholder="{{ __('Enter your receipt number') }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="branch_name">{{ __('Branch Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="branch_name" id="branch_name" class="form-control" required placeholder="{{ __('Enter branch name where you paid') }}">
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