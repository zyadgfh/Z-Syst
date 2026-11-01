@extends('layouts.auth.app')

@section('title')
    {{ __('Login') }}
@endsection

@section('main_content')
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-header text-center">
                    <div class="auth-logo mb-4">
                        <img src="{{ asset('assets/images/logo/logo.png') }}" alt="Z-Syst Logo">
                    </div>
                    <h2 class="auth-title">{{ __('Welcome to') }} <span class="text-primary">Z-Syst Pharmacy</span></h2>
                    <p class="auth-subtitle">{{ __('Welcome back, Please login to your account') }}</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="auth-form">
                    @csrf
                    
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Email Address') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" name="email" class="form-control" placeholder="{{ __('Enter your Email') }}" required autofocus>
                        </div>
                        @error('email')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Password') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password" class="form-control" placeholder="{{ __('Password') }}" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                {{ __('Remember me') }}
                            </label>
                        </div>
                        <a href="{{ route('password.request') }}" class="text-primary">
                            {{ __('Forgot Password?') }}
                        </a>
                    </div>

                    @if (config('supabase.captcha.enabled'))
                        <div class="form-group mb-3">
                            <input type="hidden" name="captcha_token" id="captcha_token" value="{{ old('captcha_token') }}">
                            <div class="h-captcha"
                                 data-sitekey="{{ config('supabase.captcha.site_key') }}"
                                 data-callback="zsystCaptchaDone"
                                 data-expired-callback="zsystCaptchaExpired"></div>
                            @error('captcha_token')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
                            <script>
                                function zsystCaptchaDone(token) {
                                    document.getElementById('captcha_token').value = token;
                                }

                                function zsystCaptchaExpired() {
                                    document.getElementById('captcha_token').value = '';
                                }

                                // Tokens are single-use: after a failed attempt the
                                // widget must be reset or the next submit is refused.
                                document.addEventListener('DOMContentLoaded', function () {
                                    var form = document.querySelector('.auth-form');
                                    if (form && window.hcaptcha) {
                                        form.addEventListener('submit', function () {
                                            setTimeout(function () {
                                                try { hcaptcha.reset(); } catch (e) {}
                                            }, 0);
                                        });
                                    }
                                });
                            </script>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary w-100 py-3">
                        {{ __('Log In') }}
                    </button>

                    <div class="auth-footer mt-4 text-center">
                        <div class="row">
                            <div class="col-6">
                                <a href="{{ route('home') }}" class="text-muted">
                                    <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Home') }}
                                </a>
                            </div>
                            <div class="col-6 text-end">
                                <a href="javascript:void()" data-bs-target="#registration-modal" data-bs-toggle="modal" class="text-primary">
                                    {{ __('Create an account') }} <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('modal')
@include('landing::web.components.signup')
@endpush

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const toggleButtons = document.querySelectorAll('.toggle-password');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
        });
    </script>
@endpush

