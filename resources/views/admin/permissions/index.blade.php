@extends('layouts.master')

@section('title')
    {{ __('roles.Assigned Role') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('roles.Permissions') }}</h4>
                    </div>
                    <div class=" justify-content-center mb-4 p-16">
                        <div class="">
                            <div class=" border-0 mt-4 rounded ">
                                <div class=" permission">
                                    <form action="{{ route('admin.permissions.store') }}" method="post"
                                        class="row ajaxform_instant_reload">
                                        @csrf

                                        <div class="col-6 form-group mb-3">
                                            <label for="user" class="required">{{ __('common.User') }}</label>
                                            <div class="gpt-up-down-arrow position-relative">
                                                <select name="user" id="user" class="form-control" required>
                                                    <option>-{{ __('roles.Select User') }}-</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ ucfirst($user->name) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span></span>
                                            </div>
                                        </div>

                                        <div class="col-6 form-group mb-3">
                                            <label for="role" class="required">{{ __('roles.Role') }}</label>
                                            <div class="gpt-up-down-arrow position-relative">
                                                <select name="roles" id="role" class="form-control" required>
                                                    <option>-{{ __('roles.Select Role') }}-</option>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}">{{ ucfirst($role->name) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span></span>
                                            </div>
                                        </div>

                                        <div class="button-group text-center mt-3">
                                            <button type="reset" class="theme-btn border-btn m-2">{{ __('common.Cancel') }}</button>
                                            <button class="theme-btn m-2 submit-btn">{{ __('common.Save') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
