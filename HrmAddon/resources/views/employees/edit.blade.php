@extends('business::layouts.master')

@section('title')
    {{ __('Edit Employee') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card border-0">
                <div class="card-bodys ">
                    <div class="table-header p-16">
                        <h4>{{ __('Edit Employee') }}</h4>
                        <a href="{{ route('hrm.employees.index') }}" class="add-order-btn rounded-2 {{ Route::is('hrm.employees.create') ? 'active' : '' }}"><i class="far fa-list" aria-hidden="true"></i> {{ __('Employee List') }}</a>
                    </div>
                    <div class="order-form-section p-16">
                        <form action="{{ route('hrm.employees.update', $employee->id) }}" method="POST"
                            class="ajaxform_instant_reload">
                            @csrf
                            @method('put')
                            <div class="add-suplier-modal-wrapper d-block">
                                <div class="row">
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Name') }}</label>
                                        <input type="text" name="name" value="{{ $employee->name }}" required class="form-control"
                                            placeholder="{{ __('Enter employee name') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Designation') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="designation_id" class="form-control table-select w-100 role" required>
                                                <option value=""> {{ __('Select one') }}</option>
                                                @foreach ($designations as $designation)
                                                    <option @selected($employee->designation_id) value="{{ $designation->id }}"> {{ ucfirst($designation->name) }}
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
                                                    <option @selected($employee->department_id) value="{{ $department->id }}"> {{ ucfirst($department->name) }}</option>
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
                                                    <option @selected($employee->shift_id) value="{{ $shift->id }}"> {{ ucfirst($shift->name) }}</option>
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
                                                    <option @selected($employee->gender == 'male') value="male">{{ __('Male')}}</option>
                                                    <option @selected($employee->gender == 'female') value="female">{{ __('Female')}}</option>
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Birth Date') }}</label>
                                        <input type="date" name="birth_date" value="{{ $employee->birth_date ?? date('Y-m-d') }}" class="form-control">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Email') }}</label>
                                        <input type="email" name="email" value="{{ $employee->email }}" class="form-control" placeholder="{{ __('Enter email address') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Country') }}</label>
                                        <input type="text" name="country" value="{{ $employee->country }}" class="form-control" placeholder="{{ __('Enter country') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Phone') }}</label>
                                        <input type="number" name="phone" value="{{ $employee->phone }}" required class="form-control" placeholder="{{ __('Enter phone number') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Salary') }}</label>
                                        <input type="number" name="amount" required value="{{ $employee->amount }}"  class="form-control" placeholder="{{ __('Enter salary amount') }}">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Join Date') }}</label>
                                        <input type="date" name="join_date" value="{{ $employee->join_date ?? date('Y-m-d') }}" class="form-control">
                                    </div>
                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('Status') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="status" class="form-control table-select w-100 role" required>
                                                <option value="">{{ __('Select one') }}</option>
                                                    <option @selected($employee->status == 'active') value="active">{{ __('Active')}}</option>
                                                    <option @selected($employee->status == 'terminated') value="terminated">{{ __('Terminate')}}</option>
                                                    <option @selected($employee->status == 'suspended') value="suspended">{{ __('Suspended')}}</option>
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
                                                <img src="{{ asset( $employee->image ?? 'assets/images/icons/upload.png') }}" id="image" class="table-img">
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
