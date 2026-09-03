@extends('layouts.master')

@section('title')
    {{ __('roles.User Create') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('roles.Add New Staff') }}</h4>
                        <div>
                            <a href="{{ route('admin.users.index') }}" class="theme-btn print-btn text-light active">
                                <i class="fas fa-list me-1"></i>
                                {{ __('common.View List') }}
                            </a>
                        </div>
                    </div>
                    <div class="tab-content order-summary-tab p-3">
                        <div class="tab-pane fade show active" id="add-new-user">
                            <div class="order-form-section">
                                <form action="{{ route('admin.users.store') }}" method="post" enctype="multipart/form-data"
                                    class="ajaxform_instant_reload">
                                    @csrf

                                    <div class="add-suplier-modal-wrapper mt-2">
                                        <div class="row">
                                            <div class="col-lg-6 mt-3">
                                                <label>{{ __('roles.Full Name') }}</label>
                                                <input type="text" name="name" required class="form-control"
                                                    placeholder="{{ __('common.Enter Name') }}">
                                            </div>

                                            <div class="col-lg-6 mt-3">
                                                <label>{{ __('common.Email') }}</label>
                                                <input type="text" name="email" required class="form-control"
                                                    placeholder="{{ __('roles.Enter Email Address') }}">
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{ __('common.Phone') }}</label>
                                                <input type="text" name="phone" class="form-control"
                                                    placeholder="{{ __('business.Enter Phone Number') }}">
                                            </div>

                                            <div class="col-lg-6 ">
                                                <label class="img-label">{{ __('common.Image') }}</label>
                                                <div class=" chosen-img d-flex align-items-center gap-2 ">
                                                    <div class="w-100">
                                                        <input type="file" accept="image/*" name="image"
                                                            class="form-control w-100 file-input-change" data-id="image">
                                                    </div>
                                                    <div class="img-wrp">
                                                        <img src="{{ asset('assets/images/icons/empty-img.svg') }}"
                                                            alt="user" id="image" class="table-img">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{ __('roles.Role') }}</label>
                                                <div class="gpt-up-down-arrow position-relative">
                                                    <select name="role" required class="select-2 form-control w-100">
                                                        <option value=""> {{ __('roles.Select a role') }}</option>
                                                        @foreach ($roles as $role)
                                                            <option value="{{ $role->name }}"
                                                                @selected(request('users') == $role->name)> {{ ucfirst($role->name) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <span></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{ __('common.Status') }}</label>
                                                <div class="gpt-up-down-arrow position-relative">
                                                    <select name="status" required class="select-2 form-control w-100">
                                                        <option value="active"> {{ __('common.Active') }}</option>
                                                        <option value="pending"> {{ __('common.Pending') }}</option>
                                                    </select>
                                                    <span></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{__('business.Password')}}</label>
                                                <div class="pass-field">
                                                    <input type="password" name="password" required class="form-control" placeholder="{{ __('business.Enter Password') }}">
                                                    <i class="far fa-eye eye-btn"></i>
                                                </div>
                                            </div>


                                            <div class="col-lg-6 mt-2">
                                                <label>{{__('roles.Confirm Password')}}</label>
                                                <div class="pass-field">
                                                    <input type="password" name="password_confirmation" required class="form-control" placeholder="{{ __('roles.Enter Confirm password') }}">
                                                    <i class="far fa-eye eye-btn"></i>
                                                </div>
                                            </div>


                                            <div class="col-lg-12">
                                                <div class="button-group text-center mt-5">
                                                    <a href=""
                                                        class="theme-btn border-btn m-2">{{ __('common.Cancel') }}</a>
                                                    <button class="theme-btn m-2 submit-btn">{{ __('common.Save') }}</button>
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
        </div>
    </div>
@endsection
