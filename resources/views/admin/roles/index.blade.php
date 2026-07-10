@extends('layouts.master')

@section('title')
    {{ __('Roles') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Roles List') }}</h4>
                    </div>
                    <div class="row mb-4 p-16">
                        @can('roles-create')
                            <div class="col-xl-4 col-lg-6 col-md-6 mt-3  ">
                                <div class=" shadow-sm border-0 h-100 role-bg rounded">
                                    <div class="row ">
                                        <div class="col-sm-5">
                                            <div class="d-flex align-items-end justify-content-center h-100">
                                                <img src="{{ asset('assets/images/icons/user-roles.svg') }}"
                                                    class="img-fluid mt-2" alt="Image" width="85">
                                            </div>
                                        </div>
                                        <div class="col-sm-7">
                                            <div class="card-body text-sm-end text-center ps-sm-0 ms-2">
                                                <a href="{{ route('admin.roles.create') }}">
                                                    <span
                                                        class="btn btn-warning btn-custom-warning fw-bold text-uppercase btn-sm mb-1">{{ __('Add New Role') }}</span>
                                                </a>
                                                <small class="mb-0 d-block text-light">{{ __('Add role, if it does not exist') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endcan
                        @foreach ($roles as $role)
                            <div class="col-xl-4 col-lg-6 col-md-6 mt-3">
                                <div class="cards shadow-sm rounded border-0">
                                    <div class="card-body admin-manage-bg">
                                        <div class="d-flex justify-content-between">
                                            <span>{{ __('Total :count users', ['count' => $role->users_count]) }}</span>
                                            <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                                                @foreach ($role->users->take(5) as $key => $user)
                                                    <li class="avatar avatar-sm pull-up position-relative">
                                                        <img class="rounded-circle border border-white avatar-img"
                                                            src="{{ $user->image ? asset($user->image) : asset('assets/images/icons/avater.svg') }}"
                                                            alt="{{ ucfirst($user->name) }}">
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-end mt-1 pt-25">
                                            <div class="role-heading">
                                                <h4 class="fw-bolder">{{ ucfirst($role->name) }} <i
                                                        class="{{ request('id') == $role->id ? 'fas fa-bell text-red' : '' }}"></i>
                                                </h4>
                                                @can('roles-update')
                                                    <a class="primary" href="{{ route('admin.roles.edit', $role->id) }}">
                                                        <small class="fw-bolder edit-role">
                                                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M10.9499 3.58959L11.6099 2.9296C12.1567 2.38284 13.0432 2.38284 13.5899 2.9296C14.1367 3.47636 14.1367 4.36282 13.5899 4.90958L12.9299 5.56957M10.9499 3.58959L6.51039 8.02913C6.17205 8.36753 5.93203 8.7914 5.81598 9.2556L5.33333 11.1862L7.26393 10.7035C7.72813 10.5875 8.152 10.3475 8.4904 10.0091L12.9299 5.56957M10.9499 3.58959L12.9299 5.56957" fill="none" stroke="#00987F" stroke-width="1.5" stroke-linejoin="round"/>
                                                                <path d="M12.6666 9.52018C12.6666 11.7118 12.6666 12.8076 12.0613 13.5452C11.9505 13.6802 11.8267 13.804 11.6917 13.9148C10.9541 14.5202 9.85827 14.5202 7.6666 14.5202H7.33333C4.81917 14.5202 3.56211 14.5202 2.78106 13.7391C2.00002 12.9581 2 11.701 2 9.18685V8.85352C2 6.66186 2 5.56604 2.60529 4.82848C2.71611 4.69345 2.83993 4.56963 2.97496 4.45881C3.71253 3.85352 4.80835 3.85352 7 3.85352" stroke="#00987F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                </svg>

                                                            {{ __('Edit Role') }}</small>
                                                    </a>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
