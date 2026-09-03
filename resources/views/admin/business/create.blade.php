@extends('layouts.admin')

@section('title')
    {{ __('business.Create Business') }}
@endsection

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card border-0">
            <div class="card-bodys ">
                <div class="table-header p-16">
                    <h4>{{__('business.Add new Store')}}</h4>
                    @can('plans-read')
                        <a href="{{ route('admin.business.index') }}" class="add-order-btn  rounded-2 {{ Route::is('admin.users.create') ? 'active' : '' }}"><i class="far fa-list" aria-hidden="true"></i> {{ __('business.Store List') }}</a>
                    @endcan
                </div>
                <div class="order-form-section p-16">
                    <form action="{{ route('admin.business.store') }}" method="POST" class="ajaxform_instant_reload">
                        @csrf
                        <div class="add-suplier-modal-wrapper d-block">
                            <div class="row">

                                <div class="col-lg-6 mb-2">
                                    <label>{{ __('common.Business Name') }}</label>
                                    <input type="text" name="companyName" required class="form-control" placeholder="{{ __('business.Enter Company Name') }}">
                                </div>

                                <div class="col-lg-6 mb-2">
                                    <label>{{__('common.Business Category')}}</label>
                                    <div class="gpt-up-down-arrow position-relative">
                                        <select name="business_category_id" required
                                                class="form-control table-select w-100 role">
                                            <option value=""> {{__('common.Select One')}}</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"> {{ ucfirst($category->name) }} </option>
                                            @endforeach
                                        </select>
                                        <span></span>
                                    </div>
                                </div>

                                <div class="col-lg-6 mb-2">
                                    <label>{{__('business.Subscription Plan')}}</label>
                                    <div class="gpt-up-down-arrow position-relative">
                                        <select name="plan_subscribe_id"
                                                class="form-control table-select w-100 role">
                                            <option value=""> {{__('business.Select Plan')}}</option>
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->id }}"> {{ ucfirst($plan->subscriptionName) }} </option>
                                            @endforeach
                                        </select>
                                        <span></span>
                                    </div>
                                </div>

                                <div class="col-lg-6 mb-2">
                                    <label>{{ __('common.Phone') }}</label>
                                    <input type="text" name="phoneNumber" required class="form-control" placeholder="{{ __('business.Enter Phone Number') }}">
                                </div>

                                <div class="col-lg-6 mb-2">
                                    <label>{{ __('common.Email') }}</label>
                                    <input type="email" name="email" required class="form-control" placeholder="{{ __('business.Enter Email') }}">
                                </div>

                                <div class="col-lg-6 mb-2">
                                    <label>{{ __('business.Shop Opening Balance') }}</label>
                                    <input type="number" name="shopOpeningBalance" required class="form-control" placeholder="{{ __('business.Enter Balance') }}">
                                </div>

                                <div class="col-lg-6 mb-2">
                                    <label>{{ __('common.Address') }}</label>
                                    <input type="text" name="address" required class="form-control" placeholder="{{ __('business.Enter Address') }}">
                                </div>

                                <div class="col-lg-6">
                                    <label>{{__('business.Password')}}</label>
                                    <div class="pass-field">
                                        <input type="password" name="password" required class="form-control" placeholder="{{ __('business.Enter Password') }}">
                                        <i class="far fa-eye eye-btn"></i>
                                    </div>
                                </div>


                                <div class="col-lg-6 ">
                                    <label class="img-label">{{ __('common.Image') }}</label>
                                    <div class=" chosen-img d-flex align-items-center gap-2 ">
                                        <div class="w-100">
                                            <input type="file" accept="image/*" name="pictureUrl" class="form-control w-100 file-input-change" data-id="image">
                                        </div>
                                        <div class="img-wrp">
                                            <img src="{{ asset('assets/images/icons/empty-img.svg') }}" alt="user"
                                            id="image" class="table-img">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="button-group text-center mt-5">
                                        <button type="reset" class="theme-btn border-btn m-2">{{ __('common.Cancel') }}</button>
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
@endsection

@push('js')
    <script src="{{ asset('assets/js/custom/custom.js') }}"></script>
@endpush
