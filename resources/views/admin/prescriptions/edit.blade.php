<div class="modal modal-md fade" id="edit-prescription-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('Edit Prescription') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post" enctype="multipart/form-data"
                    class="ajaxform_instant_reload edit-image-form mb-0">
                    @csrf
                    @method('put')

                    <div class="mt-3">
                        <label>{{ __('Customer') }} ({{ __('Optional') }})</label>
                        <select name="party_id" class="form-control" id="edit-party_id">
                            <option value="">{{ __('Select Customer') }}</option>
                            @foreach ($parties as $party)
                                <option value="{{ $party->id }}">{{ $party->name }} ({{ $party->phone }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Prescription Number') }}</label>
                        <input type="text" name="prescription_number" class="form-control" id="edit-prescription-number">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Patient Name') }}</label>
                        <input type="text" name="patient_name" class="form-control" id="edit-patient-name">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Doctor Name') }}</label>
                        <input type="text" name="doctor_name" class="form-control" id="edit-doctor-name">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Expiry Date') }}</label>
                        <input type="date" name="expires_at" class="form-control" id="edit-expires-at">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Review Status') }}</label>
                        <select name="review_status" class="form-control" id="edit-review-status">
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                        </select>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Review Notes') }}</label>
                        <textarea name="review_notes" class="form-control" id="edit-review-notes" rows="3"></textarea>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" id="edit-notes" rows="3" placeholder="{{ __('Enter notes about the prescription') }}"></textarea>
                    </div>

                    <div class="mt-3 position-relative">
                        <label class="upload-img-label">{{ __('Prescription Image') }}</label>
                        <div class="upload-img-v2 chosen-img">
                            <label class="upload-v4 d-flex align-items-center justify-content-center">
                                <div class="img-wrp">
                                    <img src="{{ asset('assets/images/icons/upload-icon.svg') }}" alt="user"
                                        id="edit-prescription-image">
                                </div>
                                <input type="file" name="image" class="d-none"
                                    data-preview="#edit-prescription-image"
                                    accept="image/*">
                            </label>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Status') }}</label>
                        <select name="status" class="form-control" id="edit-status">
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="used">{{ __('Used') }}</option>
                        </select>
                    </div>

                    <div class="col-lg-12">
                        <div class="button-group text-center mt-5">
                            <button type="reset" class="theme-btn border-btn m-2" data-bs-dismiss="modal" aria-label="Close">{{ __('Cancel') }}</button>
                            <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

