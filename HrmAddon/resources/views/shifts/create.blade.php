<div class="modal modal-lg fade common-validation-modal" id="shifts-create-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('Create Shift') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <form action="{{ route('hrm.shifts.store') }}" method="post" enctype="multipart/form-data"
                        class="ajaxform_instant_reload">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Select Name') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="name" required class="form-control table-select w-100 role">
                                        <option value=""> {{ __('Select one') }}</option>
                                            <option value="Morning">{{ __('Morning') }}</option>
                                            <option value="Day">{{ __('Day') }}</option>
                                            <option value="Evening">{{ __('Evening') }}</option>
                                            <option value="Night">{{ __('Night') }}</option>
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Break Status') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="break_status" class="form-control table-select w-100 role break-status">
                                        <option value=""> {{ __('Select a one') }}</option>
                                            <option selected value="yes">{{ __('Yes') }}</option>
                                            <option value="no">{{ __('No') }}</option>
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Start Time') }}</label>
                                <input type="time" value="{{ date('H:i') }}" required class="form-control" name="start_time">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('End Time') }}</label>
                                <input type="time" value="{{ date('H:i') }}" required class="form-control" name="end_time">
                            </div>
                             <div class="col-lg-6 mb-2 start-break-time">
                                <label>{{ __('Start Break Time') }}</label>
                                <input type="time" value="{{ date('H:i') }}" class="form-control" name="start_break_time">
                            </div>
                            <div class="col-lg-6 mb-2 start-break-time">
                                <label>{{ __('End Break Time') }}</label>
                                <input type="time" value="{{ date('H:i') }}" class="form-control" name="end_break_time">
                            </div>
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
</div>
