<div class="modal modal-md fade" id="create-prescription-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('products.Add New Prescription') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.prescriptions.store') }}" method="post" enctype="multipart/form-data" class="ajaxform_instant_reload">
                    @csrf

                    <div class="mt-3">
                        <label>{{ __('common.Customer') }} ({{ __('gateways.Optional') }})</label>
                        <select name="party_id" class="form-control">
                            <option value="">{{ __('products.Select Customer') }}</option>
                            @foreach ($parties as $party)
                                <option value="{{ $party->id }}">{{ $party->name }} ({{ $party->phone }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('products.Prescription Number') }}</label>
                        <input type="text" name="prescription_number" class="form-control" placeholder="{{ __('products.RX-123456') }}">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('products.Patient Name') }}</label>
                        <input type="text" name="patient_name" class="form-control" placeholder="{{ __('products.Patient full name') }}">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('products.Doctor Name') }}</label>
                        <input type="text" name="doctor_name" class="form-control" placeholder="{{ __('products.Doctor name') }}">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('common.Expiry Date') }}</label>
                        <input type="date" name="expires_at" class="form-control">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('products.Review Status') }}</label>
                        <select name="review_status" class="form-control">
                            <option value="pending">{{ __('common.Pending') }}</option>
                            <option value="approved">{{ __('common.Approved') }}</option>
                            <option value="rejected">{{ __('purchases.Rejected') }}</option>
                        </select>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('common.Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('products.Enter notes about the prescription') }}"></textarea>
                    </div>

                    <div class="mt-3 position-relative">
                        <label class="upload-img-label">{{ __('products.Prescription Image') }} <span class="text-danger">*</span></label>
                        <div class="chosen-img">
                            <label class="d-flex align-items-center gap-2">
                                <input type="file" name="image" class="form-control bg-light"
                                    data-preview="#prescription-img"
                                    accept="image/*" required>
                                <div class="img-wrp">
                                    <img src="{{ asset('assets/images/icons/empty-img.svg') }}" alt="user"
                                        id="prescription-img">
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="button-group text-center mt-3">
                            <button type="reset" class="theme-btn border-btn m-2" data-bs-dismiss="modal" aria-label="Close">{{ __('common.Cancel') }}</button>
                            <button class="theme-btn m-2 submit-btn">{{ __('common.Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

