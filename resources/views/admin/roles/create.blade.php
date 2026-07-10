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
                        <h4>{{ __('Add New Role & Permission') }}</h4>
                    </div>
                    <div class="row justify-content-center mt-2 roles-permissions p-16">
                        <div class="col-md-12">
                            <form action="{{ route('admin.roles.store') }}" method="post" class="row ajaxform_instant_reload">
                                @csrf

                                <div class="col-12 form-group role-input-label">
                                    <label for="name" class="required">{{ __('Role Name') }}</label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="{{ __('Enter role name') }}" required>
                                </div>

                                <div class="col-12 ">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h4 class="mt-3 mb-3 permission-title">{{ __('Select Permission') }}</h4>
                                        <div class="custom-control custom-checkbox d-flex align-items-center">
                                            <label for="" class="table-custom-checkbox">
                                                <input type="checkbox"
                                                    class="custom-control-input table-hidden-checkbox checkbox-item"
                                                    id="selectAll">
                                                <label for="selectAll"
                                                    class="table-custom-checkmark custom-control-label custom-checkmark"></label>
                                            </label>
                                            <label class="custom-control-label fw-bold"
                                                for="selectAll">{{ __('Select All') }}</label>
                                        </div>
                                    </div>

                                    <div class="top-customer-table table-container table-responsive">
                                        <table class="table">
                                            <tbody>
                                                <tr>
                                                    <th class="table-header-content">{{ __('SL') }}.</th>
                                                    <th class="text-nowrap fw-bolder table-header-content text-start">
                                                        {{ __('Setup role permissions') }}
                                                    </th>
                                                    <th class="table-header-content text-start">
                                                        {{ __('Permission') }}
                                                    </th>
                                                </tr>
                                                @foreach ($groups as $key => $group)
                                                    <tr class="table-content">
                                                        <td class="table-single-content">{{ $loop->index + 1 }}</td>
                                                        <td class="text-nowrap  text-start table-single-content">
                                                            {{ $key }}
                                                        </td>
                                                        <td class="text-start table-single-content">
                                                            <div class="d-flex">
                                                                @foreach ($group as $permission)
                                                                    <div
                                                                        class="custom-control custom-checkbox mr-3 me-lg-5 d-flex align-items-center  ">
                                                                        <label for="" class="table-custom-checkbox">
                                                                            <input type="checkbox" name="permissions[]"
                                                                                value="{{ $permission->id }}"
                                                                                class="custom-control-input table-hidden-checkbox checkbox-item "
                                                                                id="id_{{ $permission->id }}">
                                                                            <label for="id_{{ $permission->id }}"
                                                                                class="table-custom-checkmark custom-checkmark custom-control-label"></label>
                                                                        </label>

                                                                        <label class="custom-control-label"
                                                                            for="id_{{ $permission->id }}">
                                                                            {{ ucfirst(str($permission->name)->explode('-')->last()) }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>


                                    <div class="button-group text-center mt-3">
                                        <button type="reset"
                                            class="theme-btn border-btn m-2">{{ __('Reset') }}</button>
                                        <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
