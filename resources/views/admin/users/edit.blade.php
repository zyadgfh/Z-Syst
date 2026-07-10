@extends('layouts.master')

@section('title')
    {{ __('User Edit') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Edit Staff') }}</h4>
                        <div>
                            <a href="{{ route('admin.users.index') }}" class="theme-btn print-btn text-light active">
                                <i class="fas fa-list me-1"></i>
                                {{ __('View List') }}
                            </a>
                        </div>
                    </div>
                    <div class="tab-content order-summary-tab p-16">
                        <div class="tab-pane fade show active" id="add-new-user"><br>
                            <div class="order-form-section">
                                <form action="{{ route('admin.users.update', $user->id) }}" method="post" enctype="multipart/form-data" class="ajaxform_instant_reload">
                                    @csrf
                                    @method('put')

                                    <div class="add-suplier-modal-wrapper">
                                        <div class="row">
                                            <div class="col-lg-6 mt-2">
                                                <label>{{ __('Full Name') }}</label>
                                                <input type="text" name="name" value="{{ $user->name }}" required class="form-control" placeholder="{{ __('Enter Name') }}" >
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{__('Email')}}</label>
                                                <input type="text" name="email" value="{{ $user->email }}" required class="form-control" placeholder="{{ __('Enter Email Address') }}" >
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{__('Phone')}}</label>
                                                <input type="text" name="phone" value="{{ $user->phone }}" class="form-control" placeholder="{{ __('Enter Phone Number') }}" >
                                            </div>

                                            <div class="col-lg-6 ">
                                                <label class="img-label">{{ __('Image') }}</label>
                                                <div class=" chosen-img d-flex align-items-center gap-2 ">
                                                    <div class="w-100">
                                                        <input type="file" accept="image/*" name="image" class="form-control w-100 file-input-change" data-id="image">
                                                    </div>
                                                    <div class="img-wrp">
                                                        <img src="{{ asset('assets/images/icons/empty-img.svg') }}" alt="user"
                                                        id="image" class="table-img">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{__('Role')}}</label>
                                                <div>
                                                    <div class="gpt-up-down-arrow position-relative">
                                                    <select name="role" required class="select-2 form-control w-100" >
                                                        <option value=""> {{__('Select a role')}}</option>
                                                        @foreach ($roles as $role)
                                                        <option value="{{ $role->name }}" @selected($user->role == $role->name)> {{ ucfirst($role->name) }} </option>
                                                        @endforeach
                                                    </select>
                                                    <span></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{ __('Status') }}</label>
                                                <div class="gpt-up-down-arrow position-relative">
                                                    <select name="status" required class="select-2 form-control w-100">
                                                        <option value=""> {{ __('Select Status') }}</option>
                                                        <option value="active" @selected($user->status == 'active')> {{ __('Active') }}</option>
                                                        <option value="pending" @selected($user->status == 'pending')> {{ __('Pending') }}</option>
                                                    </select>
                                                    <span></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-6 mt-2">
                                                <label>{{__('Password')}}</label>
                                                <div class="pass-field">
                                                    <input type="password" name="password" class="form-control" placeholder="{{ __('Enter Password') }}">
                                                    <i class="far fa-eye eye-btn"></i>
                                                </div>
                                            </div>


                                            <div class="col-lg-6 mt-2">
                                                <label>{{__('Confirm Password')}}</label>
                                                <div class="pass-field">
                                                    <input type="password" name="password_confirmation" class="form-control" placeholder="{{ __('Enter Confirm password') }}">
                                                    <i class="far fa-eye eye-btn"></i>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="button-group text-center mt-5">
                                                    <a href="{{ route('admin.users.index',['users'=>$user->role]) }}" class="theme-btn border-btn m-2">{{__('Cancel')}}</a>
                                                    <button class="theme-btn m-2 submit-btn">{{__('Update')}}</button>
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
