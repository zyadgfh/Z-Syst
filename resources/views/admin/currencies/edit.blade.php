@extends('layouts.admin')

@section('title')
    {{ __('common.Edit Currency') }}
@endsection

@section('main_content')
    <div class="order-form-section">
        <div class="erp-table-section">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-bodys">
                        <div class="table-header p-16">
                            <h4>{{ __('common.Edit Currency') }}</h4>
                            @can('currencies-read')
                                <a href="{{ route('admin.currencies.index') }}"
                                    class="add-order-btn rounded-2 {{ Route::is('admin.currencies.create') ? 'active' : '' }}"><i
                                        class="far fa-list me-1" aria-hidden="true"></i> {{ __('common.View List') }}</a>
                            @endcan
                        </div>

                        <form action="{{ route('admin.currencies.update', $currency->id) }}" method="post"
                            enctype="multipart/form-data" class="ajaxform_instant_reload">
                            @csrf
                            @method('put')

                            <div class="row p-16">
                                <div class="col-lg-6 ">
                                    <label>{{ __('common.Name') }}</label>
                                    <input type="text" name="name" value="{{ $currency->name }}" required
                                        class="form-control" placeholder="{{ __('common.Enter Name') }}">
                                </div>
                                <div class="col-lg-6 ">
                                    <label>{{ __('common.Code') }}</label>
                                    <input type="text" name="code" value="{{ $currency->code }}" required
                                        class="form-control" placeholder="{{ __('common.Enter Code') }}">
                                </div>
                                <div class="col-lg-6 mt-2">
                                    <label>{{ __('common.Symbol') }}</label>
                                    <input type="text" name="symbol" value="{{ $currency->symbol }}"
                                        class="form-control" placeholder="{{ __('common.Enter Symbol') }}">
                                </div>

                                <div class="col-lg-6 mt-2">
                                    <label>{{ __('common.Rate') }}</label>
                                    <input type="number" name="rate" value="{{ $currency->rate }}" step="any" min="0" class="form-control" placeholder="{{ __('common.Enter Rate') }}">
                                </div>

                                <div class="col-lg-6 mt-2">
                                    <label>{{ __('common.Position') }}</label>
                                    <div class="gpt-up-down-arrow position-relative">
                                    <select name="position" class="form-control table-select w-100">
                                        <option value="">{{ __('common.Select a position') }}</option>
                                        <option value="left" @selected('left' == $currency->position)>{{ __('common.left') }}</option>
                                        <option value="right" @selected('right' == $currency->position)>{{ __('common.right') }}</option>
                                    </select>
                                    <span></span>
                                    </div>
                                </div>

                                <div class="col-lg-6 mt-2">
                                    <label>{{ __('common.Country') }}</label>
                                    <div class="gpt-up-down-arrow position-relative">
                                    <select name="country_name" class="form-control table-select w-100">
                                        <option value="">{{ __('common.Select a Country') }}</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country['name'] }}" @selected($country['name'] == $currency->country_name ?? '')>
                                                {{ $country['name'] }} </option>
                                        @endforeach
                                    </select>
                                    <span></span>
                                    </div>
                                </div>

                                <div class="col-lg-6 mt-2">
                                    <label>{{ __('common.Status') }}</label>
                                    <div class="gpt-up-down-arrow position-relative">
                                    <select name="status" required class="form-control table-select w-100">
                                        <option value="1" @selected($currency->status == 1)>{{ __('common.Active') }}</option>
                                        <option value="0" @selected($currency->status == 0)>{{ __('common.Inactive') }}</option>
                                    </select>
                                    <span></span>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="button-group text-center mt-5">
                                        <button type="reset"
                                            class="theme-btn border-btn m-2">{{ __('common.Reset') }}</button>
                                        <button class="theme-btn m-2 submit-btn">{{ __('common.Save') }}</button>
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
