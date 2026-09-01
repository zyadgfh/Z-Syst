@extends('layouts.auth.app')

@section('title')
    {{ __('Register') }}
@endsection

@section('main_content')
<div class="login-container">
    <div class="login-wrapper">
        <!-- Left Side - Branding -->
        <div class="login-branding">
            <div class="branding-content">
                <div class="branding-logo">
                    <img src="{{ asset('logo.png') }}" alt="Z-Syst Pharmacy Management">
                </div>
                <h1 class="branding-title">{{ __('Z-Syst Pharmacy') }}</h1>
                <p class="branding-subtitle">{{ __('Start managing your pharmacy today') }}</p>

                <div class="branding-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span>{{ __('Free plan available') }}</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span>{{ __('Setup in 2 minutes') }}</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span>{{ __('Secure & encrypted') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="login-form-container">
            <div class="login-card">
                <div class="login-header">
                    <h2 class="login-title">{{ __('Create Your Account') }}</h2>
                    <p class="login-subtitle">{{ __('Fill in the details to get started') }}</p>
                </div>

                <form id="registerForm" class="login-form">
                    @csrf

                    <!-- Store Name -->
                    <div class="form-group">
                        <label class="input-label" for="companyName">{{ __('Store Name') }} <span style="color:#ef4444">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="companyName" class="input" id="companyName"
                                placeholder="{{ __('e.g. Al-Shifa Pharmacy') }}" required autofocus
                                value="{{ old('companyName') }}">
                        </div>
                        <p class="input-error" id="error-companyName" style="display:none"></p>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="input-label" for="email">{{ __('Email Address') }} <span style="color:#ef4444">*</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 4H20C21.1046 4 22 4.89543 22 6V18C22 19.1046 21.1046 20 20 20H4C2.89543 20 2 19.1046 2 18V6C2 4.89543 2.89543 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <input type="email" name="email" class="input" id="email"
                                placeholder="{{ __('Enter your email') }}" required
                                value="{{ old('email') }}">
                        </div>
                        <p class="input-error" id="error-email" style="display:none"></p>
                    </div>

                    <!-- Phone & Category -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div class="form-group">
                            <label class="input-label" for="phoneNumber">{{ __('Phone Number') }} <span style="color:#ef4444">*</span></label>
                            <div class="input-wrapper">
                                <input type="text" name="phoneNumber" class="input" id="phoneNumber"
                                    placeholder="{{ __('+20 123 456 7890') }}" required
                                    value="{{ old('phoneNumber') }}">
                            </div>
                            <p class="input-error" id="error-phoneNumber" style="display:none"></p>
                        </div>
                        <div class="form-group">
                            <label class="input-label" for="business_category_id">{{ __('Category') }} <span style="color:#ef4444">*</span></label>
                            <div class="input-wrapper">
                                <select name="business_category_id" class="input" id="business_category_id" required>
                                    <option value="">{{ __('Select...') }}</option>
                                    @foreach(\App\Models\BusinessCategory::where('status', 1)->get() as $cat)
                                    <option value="{{ $cat->id }}" {{ old('business_category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="input-error" id="error-business_category_id" style="display:none"></p>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="input-label" for="password">{{ __('Password') }} <span style="color:#ef4444">*</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <input type="password" name="password" class="input" id="password"
                                placeholder="{{ __('Create a password') }}" required minlength="6">
                        </div>
                        <p class="input-error" id="error-password" style="display:none"></p>
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="submitBtn" class="btn btn-primary btn-lg w-full" style="margin-top:8px;">
                        {{ __('Create Account') }}
                    </button>

                    <!-- Footer -->
                    <div class="login-footer">
                        <p class="footer-text">
                            {{ __('Already have an account?') }}
                            <a href="{{ route('login') }}" class="footer-link">
                                {{ __('Log in') }}
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- OTP Verification Modal -->
<div id="otpModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; display:none; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:32px; max-width:400px; width:90%; text-align:center;">
        <div style="width:60px; height:60px; border-radius:50%; background:#eff6ff; display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 4H20C21.1046 4 22 4.89543 22 6V18C22 19.1046 21.1046 20 20 20H4C2.89543 20 2 19.1046 2 18V6C2 4.89543 2.89543 4 4 4Z" stroke="#3b82f6" stroke-width="2"/>
                <path d="M22 6L12 13L2 6" stroke="#3b82f6" stroke-width="2"/>
            </svg>
        </div>
        <h3 style="font-size:18px; font-weight:600; margin-bottom:4px;">{{ __('Verify Your Email') }}</h3>
        <p style="font-size:14px; color:#6b7280; margin-bottom:20px;">
            {{ __('We sent a 6-digit code to') }} <strong id="otpEmail"></strong>
        </p>
        <input type="text" id="otpInput" maxlength="6" style="width:100%; padding:14px; font-size:24px; text-align:center; letter-spacing:8px; border:2px solid #d1d5db; border-radius:10px; outline:none; font-weight:600;"
            placeholder="000000">
        <p id="otpError" style="color:#ef4444; font-size:13px; margin-top:8px; display:none;"></p>
        <button id="otpSubmitBtn" class="btn btn-primary btn-lg w-full" style="margin-top:16px;">
            {{ __('Verify & Continue') }}
        </button>
        <p style="font-size:13px; color:#6b7280; margin-top:12px;">
            {{ __('Didn\'t receive it?') }}
            <a href="#" id="otpResend" style="color:#6366f1; text-decoration:none; font-weight:500;">{{ __('Resend code') }}</a>
        </p>
        <p id="otpTimer" style="font-size:12px; color:#9ca3af; margin-top:4px;"></p>
    </div>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const otpModal = document.getElementById('otpModal');
    let userEmail = '';

    // AJAX form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.textContent = '{{ __("Creating account...") }}';

        // Clear previous errors
        document.querySelectorAll('.input-error').forEach(el => { el.style.display = 'none'; el.textContent = ''; });

        const formData = new FormData(form);

        try {
            const response = await fetch('{{ route("register") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (response.ok && data.openModal) {
                // Show OTP modal
                userEmail = data.email;
                document.getElementById('otpEmail').textContent = userEmail;
                otpModal.style.display = 'flex';
                startTimer(data.otp_expiration || 300);
            } else if (data.message) {
                // Show error
                alert(data.message);
            }
        } catch (err) {
            // Validation errors
            if (err.response) {
                const data = await err.response.json();
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const el = document.getElementById('error-' + key);
                        if (el) {
                            el.textContent = data.errors[key][0];
                            el.style.display = 'block';
                        }
                    });
                }
            }
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = '{{ __("Create Account") }}';
        }
    });

    // OTP Submit
    document.getElementById('otpSubmitBtn').addEventListener('click', async function() {
        const otp = document.getElementById('otpInput').value;
        const errorEl = document.getElementById('otpError');
        errorEl.style.display = 'none';

        try {
            const response = await fetch('{{ route("otp-submit") }}', {
                method: 'POST',
                body: JSON.stringify({ email: userEmail, otp: otp }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            const data = await response.json();

            if (response.ok) {
                // OTP verified — submit the form for real
                const hiddenForm = document.createElement('form');
                hiddenForm.method = 'POST';
                hiddenForm.action = '{{ route("register") }}';

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                hiddenForm.appendChild(csrf);

                const fd = new FormData(form);
                for (const [key, value] of fd.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    hiddenForm.appendChild(input);
                }

                document.body.appendChild(hiddenForm);
                hiddenForm.submit();
            } else {
                errorEl.textContent = data.message;
                errorEl.style.display = 'block';
            }
        } catch (err) {
            errorEl.textContent = '{{ __("Something went wrong") }}';
            errorEl.style.display = 'block';
        }
    });

    // OTP Resend
    document.getElementById('otpResend').addEventListener('click', async function(e) {
        e.preventDefault();
        try {
            await fetch('{{ route("otp-resend") }}', {
                method: 'POST',
                body: JSON.stringify({ email: userEmail }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            startTimer(300);
        } catch (err) {}
    });

    // Timer
    function startTimer(seconds) {
        const timerEl = document.getElementById('otpTimer');
        const resendBtn = document.getElementById('otpResend');
        resendBtn.style.display = 'none';

        const interval = setInterval(() => {
            seconds--;
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            timerEl.textContent = '{{ __("Resend in") }} ' + m + ':' + String(s).padStart(2, '0');
            if (seconds <= 0) {
                clearInterval(interval);
                timerEl.textContent = '';
                resendBtn.style.display = 'inline';
            }
        }, 1000);
    }
});
</script>
@endpush
