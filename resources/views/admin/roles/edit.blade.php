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
                        <h4>{{ __('Edit Role') }}</h4>
                    </div>
                    <div class="row justify-content-center mt-2 roles-permissions p-16">
                        <div class="col-md-12">
                            @if(auth()->user()->can('settings-view'))
                            <div class="alert alert-info d-flex align-items-center justify-content-between mb-3">
                                <span>
                                    <i class="fas fa-cog me-2"></i>
                                    {{ __('Configure default settings for all users with this role.') }}
                                </span>
                                <a href="{{ route('admin.app-settings.index') }}?scope_type=role&scope_id={{ $role->id }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-sliders-h me-1"></i>{{ __('Role Settings') }}
                                </a>
                            </div>
                            @endif

                            <form action="{{ route('admin.roles.update', $role->id) }}" method="post"
                                class="row ajaxform_instant_reload">
                                @csrf
                                @method('PUT')

                                <div class="col-12 form-group role-input-label">
                                    <label for="name" class="required">{{ __('Role Name') }}</label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ $role->name }}" placeholder="{{ __('Enter role name') }}" required>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h4 class="mt-3 mb-3 permission-title">{{ __('Role Permissions') }}</h4>
                                        <div class="custom-control custom-checkbox d-flex align-items-center">
                                            <label for="" class="table-custom-checkbox">
                                                <input type="checkbox"
                                                    class="custom-control-input table-hidden-checkbox checkbox-item"
                                                    id="selectAll">
                                                <label for="selectAll"
                                                    class="table-custom-checkmark custom-checkmark"></label>
                                            </label>
                                            <label class="custom-control-label fw-bold" for="selectAll">{{ __('Select All') }}</label>
                                        </div>
                                    </div>

                                    <div class="top-customer-table table-container table-responsive">
                                        <table class="table">
                                            <tbody>
                                                <tr>
                                                    <th class="text-start table-header-content">{{ __('SL') }}.</th>
                                                    <th class="text-nowrap fw-bolder text-start table-header-content">
                                                        {{ __('Setup role permissions') }}
                                                    </th>
                                                    <th class="table-header-content text-start">
                                                        {{ __('Permission') }}
                                                    </th>
                                                </tr>
                                                @foreach ($groups as $key => $group)
                                                    <tr class="table-content">
                                                        <td class="table-single-content text-start">{{ $loop->index + 1 }}
                                                        </td>
                                                        <td class="text-nowrap text-start table-single-content">
                                                            {{ $key }}
                                                        </td>
                                                        <td class="table-single-content">
                                                            <div class="d-flex">
                                                                @foreach ($group as $permission)
                                                                    <div class="custom-control custom-checkbox mr-3 me-lg-5 d-flex align-items-center">
                                                                        <label for="" class="table-custom-checkbox">
                                                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="custom-control-input table-hidden-checkbox checkbox-item" id="id_{{ $permission->id }}" @checked($role->hasPermissionTo($permission->name))>
                                                                            <label for="id_{{ $permission->id }}" class="table-custom-checkmark custom-checkmark "></label>
                                                                        </label>
                                                                        <label class="custom-control-label" for="id_{{ $permission->id }}">
                                                                            {{ ucfirst(str($permission->name)->explode('-')->last() == 'list' ? 'All ' . str($permission->name)->explode('-')->last() : str($permission->name)->explode('-')->last()) }}
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
    </div>
@endsection
