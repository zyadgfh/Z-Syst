@extends('layouts.auth.app')

@section('title')
    {{ __('Login') }}
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
                <p class="branding-subtitle">{{ __('Professional Pharmacy Management System') }}</p>
                
                <div class="branding-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span>{{ __('Inventory Management') }}</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 7L9 18L4 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span>{{ __('E-Prescriptions') }}</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span>{{ __('Analytics & Reports') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-form-container">
            <div class="login-card">
                <div class="login-header">
                    <h2 class="login-title">{{ __('Welcome Back') }}</h2>
                    <p class="login-subtitle">{{ __('Enter your credentials to access your account') }}</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf
                    
                    <!-- Email Field -->
                    <div class="form-group">
                        <label class="input-label" for="email">{{ __('Email Address') }}</label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 4H20C21.1046 4 22 4.89543 22 6V18C22 19.1046 21.1046 20 20 20H4C2.89543 20 2 19.1046 2 18V6C2 4.89543 2.89543 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <input 
                                type="email" 
                                name="email" 
                                class="input" 
                                id="email"
                                placeholder="{{ __('Enter your email') }}" 
                                required 
                                autofocus
                                value="{{ old('email') }}"
                            >
                        </div>
                        @error('email')
                            <p class="input-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label class="input-label" for="password">{{ __('Password') }}</label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 12C8 12 9.5 14 12 14C14.5 14 16 12 16 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <input 
                                type="password" 
                                name="password" 
                                class="input" 
                                id="password"
                                placeholder="{{ __('Enter your password') }}" 
                                required
                            >
                            <button type="button" class="input-action toggle-password" data-target="password">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 12s4-8 11-8 11 8 11 8 11-8 11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15 7l3 3-3 3M10 7l-3 3 3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="input-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember & Forgot Password -->
                    <div class="form-options">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkbox-label">{{ __('Remember me') }}</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="forgot-link">
                            {{ __('Forgot Password?') }}
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-lg w-full">
                        {{ __('Log In') }}
                    </button>

                    <!-- Footer -->
                    <div class="login-footer">
                        <p class="footer-text">
                            {{ __('Don\'t have an account?') }}
                            <a href="{{ route('register') }}" class="footer-link">
                                {{ __('Create an account') }}
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Login Container */
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-background);
    padding: var(--space-lg);
}

.login-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-2xl);
    max-width: 1200px;
    width: 100%;
    background: var(--color-background);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

@media (max-width: 1024px) {
    .login-wrapper {
        grid-template-columns: 1fr;
        max-width: 500px;
    }
    
    .login-branding {
        display: none;
    }
}

/* Left Side - Branding */
.login-branding {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
    padding: var(--space-3xl);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.login-branding::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M50 0C22.3858 0 0 22.3858 0 50C0 77.6142 22.3858 100 50 100C77.6142 100 100 77.6142 100 50C100 22.3858 77.6142 0 50 0ZM50 90C27.9086 90 10 72.0914 10 50C10 27.9086 27.9086 10 50 10C72.0914 10 90 27.9086 90 50Z' fill='rgba(255,255,255,0.05)'/%3E%3C/svg%3E");
    opacity: 0.5;
}

.branding-content {
    position: relative;
    z-index: 1;
    text-align: center;
    color: white;
}

.branding-logo {
    margin-bottom: var(--space-xl);
}

.branding-logo img {
    max-width: 200px;
    height: auto;
}

.branding-title {
    font-size: 32px;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    margin-bottom: var(--space-sm);
    color: white;
}

.branding-subtitle {
    font-size: 16px;
    font-weight: 400;
    font-family: 'Open Sans', sans-serif;
    margin-bottom: var(--space-2xl);
    color: rgba(255, 255, 255, 0.9);
}

.branding-features {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
    text-align: left;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    color: white;
    font-size: 14px;
    font-weight: 500;
}

.feature-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: var(--radius-md);
    flex-shrink: 0;
}

/* Right Side - Login Form */
.login-form-container {
    padding: var(--space-3xl);
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-card {
    width: 100%;
    max-width: 400px;
}

.login-header {
    text-align: center;
    margin-bottom: var(--space-2xl);
}

.login-title {
    font-size: 28px;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    color: var(--color-foreground);
    margin-bottom: var(--space-sm);
}

.login-subtitle {
    font-size: 14px;
    font-weight: 400;
    font-family: 'Open Sans', sans-serif;
    color: var(--color-muted);
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: var(--space-md);
    color: var(--color-muted);
    pointer-events: none;
    display: flex;
    align-items: center;
}

.input-wrapper .input {
    padding-left: 48px;
    padding-right: 48px;
}

.input-action {
    position: absolute;
    right: var(--space-md);
    background: transparent;
    border: none;
    cursor: pointer;
    padding: var(--space-xs);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-muted);
    transition: color 200ms ease;
}

.input-action:hover {
    color: var(--color-primary);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    cursor: pointer;
}

.checkbox-wrapper input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--color-primary);
    cursor: pointer;
}

.checkbox-label {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-foreground);
}

.forgot-link {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-primary);
    text-decoration: none;
    transition: color 200ms ease;
}

.forgot-link:hover {
    color: var(--color-accent);
}

.login-footer {
    text-align: center;
    margin-top: var(--space-xl);
}

.footer-text {
    font-size: 14px;
    color: var(--color-muted);
}

.footer-link {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 200ms ease;
}

.footer-link:hover {
    color: var(--color-accent);
}
</style>
@endpush

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('svg');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a22.92 22.92 0 0 1 0-14.94" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a22.92 22.92 0 0 0 0 14.94" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8 11-8 11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M15 7l3 3-3 3M10 7l-3 3 3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                `;
            }
        });
    });
});
</script>
@endpush
