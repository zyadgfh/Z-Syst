@extends('layouts.auth.app')

@section('title')
    {{ __('Login') }}
@endsection

@section('main_content')
    <div class="footer position-relative">
        <div class="footer-logo w-100  d-flex align-items-center justify-content-center">
            <img src="{{ asset('assets/images/login/login-logo.svg') }}" alt="">
        </div>
        <div class="mybazar-login-section ">
            <div class="d-flex align-items-center justify-content-center ">
                <div class="mybazar-login-wrapper ">
                    <div class="login-wrapper">
                        <div class="login-body w-100">
                            <h2 class="text-center login-title">{{ __('Welcome to') }}<span>{{ __(env('APP_NAME')) }}</span></h2>
                            <h6 class="text-center login-para">{{ __('Welcome back, Please login in to your account') }}</h6>
                            <form method="POST" action="{{ route('login') }}" class="ajaxform_instant_reload">
                                @csrf
                                <div class="input-group">
                                    <span><img src="{{ asset('assets/images/icons/user.png') }}" alt="img"></span>
                                    <input type="email" name="email" class="form-control email"
                                        placeholder="{{ __('Enter your Email') }}">
                                </div>

                                <div class="input-group">
                                    <span><img src="{{ asset('assets/images/icons/lock.png') }}" alt="img"></span>
                                    <span class="hide-pass">
                                        <img src="{{ asset('assets/images/icons/Hide.svg') }}" alt="img">
                                        <img src="{{ asset('assets/images/icons/show.svg') }}" alt="img">
                                    </span>
                                    <input type="password" name="password" class="form-control password" placeholder="{{ __('Password') }}">
                                </div>

                                <div class="mt-lg-3 mb-0 forget-password">
                                    <label class="custom-control-label">
                                        <input type="checkbox" name="remember" class="custom-control-input">
                                        <span>{{ __('Remember me') }}</span>
                                    </label>
                                    <a href="{{ route('password.request') }}">{{ __('Forgot Password?') }}</a>
                                </div>

                                <button type="submit" class="btn login-btn submit-btn">{{ __('Log In') }}</button>


                                <div class="row d-flex flex-wrap mt-2 justify-content-between">
                                    <div class="col">
                                        <a href="{{ route('home') }}">{{ __("Back to Home") }}</a>
                                    </div>
                                    <div class="col text-end">
                                        <a class="text-primary" href="javascript:void()" data-bs-target="#registration-modal" data-bs-toggle="modal">{{ __("Create an account.") }}</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <img class="position-absolute illistration-img1  bottom-0" src="{{ asset('assets/images/login/loginillustrator2.svg') }}" alt="" srcset="">
        <img class="position-absolute illistration-img2 bottom-0 " src="{{ asset('assets/images/login/loginillustrator1.svg') }}" alt="" srcset="">
    </div>
    <input type="hidden" data-model="Login" id="auth">
@endsection

@push('modal')
@include('landing::web.components.signup')
@endpush

@push('js')
    <script src="{{ asset('assets/js/auth.js') }}"></script>
@endpush
