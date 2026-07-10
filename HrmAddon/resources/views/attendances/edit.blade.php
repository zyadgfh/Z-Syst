<div class="modal modal-lg fade common-validation-modal editModal" id="attendances-edit-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('Edit Attendance') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <form action="" method="post" enctype="multipart/form-data"
                        class="ajaxform_instant_reload editAttendanceForm">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Employee') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="employee_id" id="employee_id" required class="form-control get-employee-shift">
                                        <option value="">{{ __('Select a One') }}</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Shift') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="shift_id" required class="form-control shift-select" @readonly(true)>
                                        <option value="">{{ __('Select a One') }}</option>
                                        {{-- Options will be populated via JS --}}
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Month') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="month" id="month" required class="form-control">
                                        <option value="">{{ __('Select One') }}</option>
                                        @for ($month = 1; $month <= 12; $month++)
                                        <option {{ $month == date('m') ? 'selected' : '' }} value="{{ strtolower(date('F', mktime(0, 0, 0, $month, 1))) }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                        @endfor
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label class="required">{{__('Date')}}</label>
                                <input type="date" name="date" required value="{{ date('Y-m-d') }}" id="date" class="form-control">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label class="required">{{ __('Time In') }}</label>
                                <input type="time" name="time_in" value="{{ date('H:i') }}" id="time_in" required class="form-control">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label class="required">{{ __('Time Out') }}</label>
                                <input type="time" name="time_out" value="{{ date('H:i') }}" id="time_out" required class="form-control">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{__('Note')}}</label>
                                <textarea name="note" id="note" class="form-control" placeholder="{{ __('Enter note') }}"></textarea>
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
<input type="hidden" value="{{ route('hrm.attendances.getShift') }}" id="get-shift">
