@extends('layouts.master')

@section('title')
    {{ __('business.Edit Business') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card border-0">
                <div class="card-body">
                    <div class="table-header p-16">
                        <h4>{{ __('business.Edit Business') }}</h4>
                        @can('plans-read')
                            <a href="{{ route('admin.business.index') }}"
                                class="add-order-btn rounded-2 {{ Route::is('admin.users.create') ? 'active' : '' }}"><i
                                    class="far fa-list" aria-hidden="true"></i> {{ __('business.Business List') }}</a>
                        @endcan
                    </div>
                    <div class="order-form-section p-16">
                        <form action="{{ route('admin.business.update', $business->id) }}" method="POST"
                            class="ajaxform_instant_reload">
                            @csrf
                            @method('PUT')
                            <div class="add-suplier-modal-wrapper d-block">
                                <div class="row">

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('common.Business Name') }}</label>
                                        <input type="text" name="companyName" value="{{ $business->companyName }}"
                                            required class="form-control" placeholder="{{ __('business.Enter Company Name') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('common.Business Category') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="business_category_id" required
                                                class="form-control table-select w-100 role">
                                                <option value=""> {{ __('business.Select Business Category') }}</option>
                                                @foreach ($categories as $category)
                                                    <option @selected($category->id == $business->business_category_id) value="{{ $category->id }}">
                                                        {{ ucfirst($category->name) }} </option>
                                                @endforeach
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('business.Subscription Plan') }}</label>
                                        <div class="gpt-up-down-arrow position-relative">
                                            <select name="plan_subscribe_id" class="form-control table-select w-100 role">
                                                <option value="">{{ __('common.Select One') }}</option>
                                                @foreach ($plans as $plan)
                                                    <option @selected($plan->id == $business->plan_subscribe_id) value="{{ $plan->id }}">
                                                        {{ ucfirst($plan->subscriptionName) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span></span>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('common.Phone') }}</label>
                                        <input type="text" name="phoneNumber" value="{{ $business->phoneNumber }}"
                                            required class="form-control" placeholder="{{ __('business.Enter Phone Number') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('common.Email') }}</label>
                                        <input type="email" name="email" value="{{ $user->email }}"
                                            class="form-control" placeholder="{{ __('business.Enter Email') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('business.Shop Opening Balance') }}</label>
                                        <input type="number" name="shopOpeningBalance"
                                            value="{{ $business->shopOpeningBalance }}" required class="form-control"
                                            placeholder="{{ __('business.Enter Balance') }}">
                                    </div>

                                    <div class="col-lg-6 mb-2">
                                        <label>{{ __('common.Address') }}</label>
                                        <input type="text" name="address" value="{{ $business->address }}" required
                                            class="form-control" placeholder="{{ __('business.Enter Address') }}">
                                    </div>

                                    <div class="col-lg-6">
                                        <label>{{__('business.Password')}} <small class="text-muted">({{ __('business.leave blank to keep current') }})</small></label>
                                        <div class="pass-field">
                                            <input type="password" name="password" class="form-control" placeholder="{{ __('business.Enter Password') }}">
                                            <i class="far fa-eye eye-btn"></i>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="row align-items-center justify-content-center">
                                            <div class="col-10">
                                                <label class="img-label">{{ __('common.Image') }}</label>
                                                <input type="file" accept="image/*" name="pictureUrl"
                                                    class="form-control file-input-change" data-id="image">
                                            </div>
                                            <div class="col-2 mt-4">
                                                <img src="{{ asset($business->pictureUrl ?? 'assets/images/icons/upload.png') }}"
                                                    id="image" class="table-img">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="button-group text-center mt-5">
                                            <button type="reset"
                                                class="theme-btn border-btn m-2">{{ __('common.Cancel') }}</button>
                                            <button class="theme-btn m-2 submit-btn">{{ __('common.Update') }}</button>
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

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
