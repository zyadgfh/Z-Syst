@extends('business::layouts.master')

@section('title')
    {{ __('Create Employee') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card border-0">
                <div class="card-bodys ">
                    <div class="table-header p-16">
                        <h4>{{ __('Add new Employee') }}</h4>
                        <a href="{{ route('hrm.employees.index') }}" class="add-order-btn rounded-2 {{ Route::is('hrm.employees.create') ? 'active' : '' }}"><i class="far fa-list" aria-hidden="true"></i> {{ __('Employee List') }}</a>
                    </div>
                    <div class="order-form-section p-16">
                        <form action="{{ route('hrm.employees.store') }}" method="POST"
                            class="ajaxform_instant_reload">
                            @csrf
                            <div class="add-suplier-modal-wrapper d-block">
                                <div class="row">
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Name') }}</label>
                                        <input type="text" name="name" required class="form-control"
                                            placeholder="{{ __('Enter employee name') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Designation') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="designation_id" class="form-control table-select w-100 role" required>
                                                <option value=""> {{ __('Select one') }}</option>
                                                @foreach ($designations as $designation)
                                                    <option value="{{ $designation->id }}"> {{ ucfirst($designation->name) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Department') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="department_id" class="form-control table-select w-100 role" required>
                                                <option value=""> {{ __('Select one') }}</option>
                                                @foreach ($departments as $department)
                                                    <option value="{{ $department->id }}"> {{ ucfirst($department->name) }}</option>
                                                @endforeach
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Shift') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="shift_id" class="form-control table-select w-100 role" required>
                                                <option value=""> {{ __('Select one') }}</option>
                                                @foreach ($shifts as $shift)
                                                    <option value="{{ $shift->id }}"> {{ ucfirst($shift->name) }}</option>
                                                @endforeach
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Gender') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="gender" class="form-control table-select w-100 role" required>
                                                <option value=""> {{ __('Select one') }}</option>
                                                    <option value="male">{{ __('Male')}}</option>
                                                    <option value="female">{{ __('Female')}}</option>
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Birth Date') }}</label>
                                        <input type="date" name="birth_date" value="{{ date('Y-m-d') }}" class="form-control">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Email') }}</label>
                                        <input type="email" name="email" class="form-control" placeholder="{{ __('Enter email address') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Country') }}</label>
                                        <input type="text" name="country" class="form-control" placeholder="{{ __('Enter country') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Phone') }}</label>
                                        <input type="number" name="phone" required class="form-control" placeholder="{{ __('Enter phone number') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Salary') }}</label>
                                        <input type="number" name="amount" required class="form-control" placeholder="{{ __('Enter salary amount') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Join Date') }}</label>
                                        <input type="date" name="join_date" value="{{ date('Y-m-d') }}" class="form-control">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Status') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="status" class="form-control table-select w-100 role" required>
                                                <option value="">{{ __('Select one') }}</option>
                                                    <option value="active">{{ __('Active')}}</option>
                                                    <option value="terminated">{{ __('Terminate')}}</option>
                                                    <option value="suspended">{{ __('Suspended')}}</option>
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="row">
                                            <div class="col-10">
                                                <label class="img-label">{{ __('Image') }}</label>
                                                <input type="file" accept="image/*" name="image" class="form-control file-input-change" data-id="image">
                                            </div>
                                            <div class="col-2 align-self-center mt-3">
                                                <img src="{{ asset('assets/images/icons/upload.png') }}" id="image" class="table-img">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="button-group text-center mt-5">
                                            <button type="reset" class="theme-btn border-btn m-2">{{ __('Reset') }}</button>
                                            <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
