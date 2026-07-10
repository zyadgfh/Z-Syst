<div class="modal modal-lg fade common-validation-modal editModal" id="leaves-edit-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('Edit Leave') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <form action="" method="post" enctype="multipart/form-data"
                        class="ajaxform_instant_reload editLeaveForm">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Employee') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="employee_id" required id="employee_id" class="form-control get-employee-department">
                                        <option value="">{{ __('Selectm a One') }}</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                             <div class="col-lg-6 mb-2">
                                <label>{{ __('Department') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="department_id" required class="form-control department-select" @readonly(true)>
                                        <option value="">{{ __('Select a One') }}</option>
                                        {{-- Options will be populated via JS --}}
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Leave Type') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="leave_type_id" required id="leave_type_id" class="form-control">
                                        <option value="">{{ __('Select a One') }}</option>
                                        @foreach ($leave_types as $leave_type)
                                            <option value="{{ $leave_type->id }}">{{ $leave_type->name }}</option>
                                        @endforeach
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
                                <label class="required">{{__('Start Date')}}</label>
                                <input type="date" name="start_date" id="start_date" required class="form-control leave_edit_start_date">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label class="required">{{ __('End Date') }}</label>
                                <input type="date" name="end_date" id="end_date" required class="form-control leave_edit_end_date">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label class="required">{{ __('Leave Duration') }}</label>
                                <input type="number" name="leave_duration" id="leave_duration" readonly class="form-control leave_edit_duration">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Status') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="pending">{{ __('Pending') }}</option>
                                        <option value="approved">{{ __('Approved') }}</option>
                                        <option value="rejected">{{ __('Rejected') }}</option>
                                    </select>
                                    <span></span>
                                </div>
                            </div>

                             <div class="col-lg-6 mb-2">
                                <label>{{__('Description')}}</label>
                                <textarea name="description" class="form-control" id="description" placeholder="{{ __('Enter Description') }}"></textarea>
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

<input type="hidden" value="{{ route('hrm.leaves.get.department') }}" id="get-department">
