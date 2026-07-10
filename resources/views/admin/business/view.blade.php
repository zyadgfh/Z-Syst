<div class="modal fade" id="business-view-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('Business View') }} (<span class="business_name"></span>)</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <div class="row mt-2">
                        <div class="col-12">
                            <img width="100px" width="100px" class="rounded-circle border-2" src=""
                                id="image" alt="">
                        </div>
                    </div>
                    <div class="row align-items-center mt-4">
                        <div class="col-md-4">
                            <p>{{ __('Business Name') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p class="business_name"></p>
                        </div>
                    </div>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('Business Category') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="category"></p>
                        </div>
                    </div>
                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('Phone') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="phone"></p>
                        </div>
                    </div>

                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('Address') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="address"></p>
                        </div>
                    </div>
                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('Package') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="package"></p>
                        </div>
                    </div>
                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('Upgrade Date') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="last_enroll"></p>
                        </div>
                    </div>
                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('Expired Date') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="expired_date"></p>
                        </div>
                    </div>
                    <div class="row align-items-center mt-3">
                        <div class="col-md-4">
                            <p>{{ __('Created date') }}</p>
                        </div>
                        <div class="col-1">
                            <p>:</p>
                        </div>
                        <div class="col-md-7">
                            <p id="created_date"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
