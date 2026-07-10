<div class="modal modal-lg fade common-validation-modal" id="payrolls-create-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('Create Payroll') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <form action="{{ route('hrm.payrolls.store') }}" method="post" enctype="multipart/form-data"
                        class="ajaxform_instant_reload">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6 mb-2">
                                <label class="required">{{ __('Employee') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="employee_id" class="form-control empAmount" required>
                                        <option value="">{{ __('Select a One') }}</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                        @endforeach
                                    </select>
                                    <span></span>
                                </div>
                            </div>

                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Payment Year') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select class="form-control" name="payemnt_year">
                                        @for ($i = date('Y'); $i >= 2022; $i--)
                                            <option @selected($i == date('Y')) value="{{ $i }}">
                                                {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                    <span></span>
                                </div>
                            </div>

                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Month') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="month" id="month" class="form-control" required>
                                        <option value="">{{ __('Select a One') }}</option>
                                        @for ($month = 1; $month <= 12; $month++)
                                            <option {{ $month == date('m') ? 'selected' : '' }}
                                                value="{{ strtolower(date('F', mktime(0, 0, 0, $month, 1))) }}">
                                                {{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                                        @endfor
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Date') }}</label>
                                <input type="date" name="date" value="{{ date('Y-m-d') }}" class="form-control">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label class="required">{{ __('Amount') }}</label>
                                <input type="number" name="amount" readonly class="form-control amountInput"
                                    placeholder="{{ __('Enter amount') }}">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label class="required">{{ __('Payment Type') }}</label>
                                <div class="gpt-up-down-arrow position-relative">
                                    <select name="payment_type_id" required class="form-control">
                                        <option value="">{{ __('Select a One') }}</option>
                                        @foreach ($payment_types as $payment_type)
                                            <option value="{{ $payment_type->id }}">{{ $payment_type->name }}</option>
                                        @endforeach
                                    </select>
                                    <span></span>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Note') }}</label>
                                <textarea name="note" class="form-control" placeholder="{{ __('Enter note') }}"></textarea>
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
<input type="hidden" value="{{ route('hrm.payrolls.getEmpAmount') }}" id="get-empAmount">
