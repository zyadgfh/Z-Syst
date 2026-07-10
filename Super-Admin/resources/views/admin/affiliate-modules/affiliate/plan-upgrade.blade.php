<div class="modal modal-md fade" id="business-upgrade-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content b-radious-24">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('Upgrade Plan') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post" enctype="multipart/form-data"
                    class="ajaxform_instant_reload upgradePlan">
                    @csrf
                    @method('put')

                    <div class="mt-3">
                        <label>{{ __('Business Name') }}</label>
                        <input class="form-control" id="business_name" readonly>
                        <input name="business_id" id="business_id" type="hidden">
                    </div>

                    <div class="mt-3">
                        <label for="plan_id">{{ __('Select A Plan') }}</label>
                        <div class="gpt-up-down-arrow position-relative">
                            <select name="plan_id" id="plan_id" class="form-control">
                                <option value="">{{ __('Select One') }}</option>
                                @foreach ($plans as $plan)
                                    <option data-price="{{ $plan->offerPrice ?? $plan->subscriptionPrice }}" value="{{ $plan->id }}">{{ $plan->subscriptionName }}</option>
                                @endforeach
                            </select>
                            <span></span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Price') }}</label>
                        <input class="form-control plan-price" name="price" type="number" step="any" placeholder="{{ __('Enter plan price or select a plan') }}">
                    </div>

                    <div class="mt-3">
                        <label for="gateway_id">{{ __('Payment Gateways') }}</label>
                        <div class="gpt-up-down-arrow position-relative">
                            <select name="gateway_id" id="gateway_id" class="form-control">
                                <option value="">{{ __('Select A payment gateway') }}</option>
                                @foreach ($gateways as $gateway)
                                    <option value="{{ $gateway->id }}">{{ $gateway->name }}</option>
                                @endforeach
                            </select>
                            <span></span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Notes') }}</label>
                        <textarea name="notes" id="notes" class="form-control" placeholder="{{ __('Enter notes') }}">{{ 'Plan subscribed by ' . auth()->user()->name }}</textarea>
                    </div>

                    <div class="col-lg-12">
                        <div class="button-group text-center mt-5">
                            <button type="reset" class="theme-btn border-btn m-2">{{ __('Reset') }}</button>
                            <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
