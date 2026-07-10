    {{-- Approved Payment modal start --}}
    <div class="modal fade" id="payment-view-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">{{__('Approved Payment')}}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="personal-info">


                        <div class="row align-items-center mt-3">
                            <div class="col-md-5">
                                <p>{{__('Date & Time')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                                <p id="date">{{__('24 Jan 2024, 10:30')}}</p>
                            </div>
                        </div>

                        <div class="row align-items-center mt-2">
                            <div class="col-md-5">
                                <p>{{__('Shop Name')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                            <p id="name"></p>
                            </div>
                        </div>


                        <div class="row align-items-center mt-2">
                            <div class="col-md-5">
                                <p>{{__('Payment Method')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                                <p>{{__('Bank')}}</p>
                            </div>
                        </div>

                        <div class="row align-items-center mt-2">
                            <div class="col-md-5">
                                <p>{{__('Withdraw Amount')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                                <p id="amount">{{__('$0')}}</p>
                            </div>
                        </div>

                        <div class="row align-items-center mt-2">
                            <div class="col-md-5">
                                <p>{{__('Status')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                                <p class="unpaid-status-2" id="status"></p>
                            </div>
                        </div>


                        <h6 class="fw-bold mt-3">{{__('Account Information')}}</h6>

                        <div class="row align-items-center mt-2">
                            <div class="col-md-5">
                                <p>{{__('A/C Name')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                                <p>{{__('Shaidul Islam')}}</p>
                            </div>
                        </div>

                        <div class="row align-items-center mt-2">
                            <div class="col-md-5">
                                <p>{{__('Bank Name')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                                <p>{{__('Dutch-Bangla Bank PLC')}}</p>
                            </div>
                        </div>

                        <div class="row align-items-center mt-2">
                            <div class="col-md-5">
                                <p>{{__('A/C Number')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                                <p>{{__('365214512236')}}</p>
                            </div>
                        </div>

                        <div class="row align-items-center mt-2">
                            <div class="col-md-5">
                                <p>{{__('Branch Name')}}</p>
                            </div>
                            <div class="col-1">:</div>
                            <div class="col-md-6">
                                <p>{{__('Dhaka')}}</p>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="button-group text-center mt-5">
                                <button type="reset" class="theme-btn border-btn m-2">{{__('Rejected')}}</button>
                                <button class="theme-btn m-2 submit-btn">{{__('Approve')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Approved Payment modal end --}}


    <div class="modal modal-md fade" id="withdrawal-payment-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content b-radious-24">
                <div class="modal-header">
                    <div class="Withdrawal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{__('Approve Withdrawal Payment')}}</h5>
                        <p>{{__('Have you Sent')}} <span>{{__('$250.00?')}}</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post" enctype="multipart/form-data"
                        class="ajaxform_instant_reload affiliateModalApproveForm">
                        <!-- CSRF and method spoofing removed -->
                        <div class="mt-1">
                            <label>{{__('Transaction Number')}}</label>
                            <input class="form-control" type="text" step="any"
                                placeholder="{{__('Enter transaction Number')}}">
                        </div>
                        <div class="mt-1">
                            <label>{{__('Upload Receipt')}} </label>
                            <input class="form-control" type="file" id="formFile">
                        </div>
                        <div class="col-lg-12">
                            <div class="button-group text-center mt-3">
                                <button type="reset" class="theme-btn border-btn m-2">{{__('Cancel')}}</button>
                                <button class="theme-btn m-2 submit-btn">{{__('Submit')}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
