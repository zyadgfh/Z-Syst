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

                <!-- Clerk Authentication -->
                @if(env('VITE_CLERK_PUBLISHABLE_KEY'))
                <div class="clerk-divider">
                    <span>or</span>
                </div>
                <div class="clerk-auth-section">
                    <p class="text-center text-sm text-gray-500 mb-3">{{ __('Sign in with Clerk') }}</p>
                    <div id="clerk-sign-in"></div>
                    <div id="clerk-sign-up" style="display: none;"></div>
                    <div class="text-center mt-3">
                        <button type="button" id="clerk-toggle" class="text-sm text-blue-600 hover:underline">
                            {{ __('Need an account? Sign up') }}
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

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

    // Clerk sign-in/sign-up toggle
    const clerkToggle = document.getElementById('clerk-toggle');
    const clerkSignIn = document.getElementById('clerk-sign-in');
    const clerkSignUp = document.getElementById('clerk-sign-up');
    if (clerkToggle && clerkSignIn && clerkSignUp) {
        clerkToggle.addEventListener('click', function() {
            const isSignIn = clerkSignIn.style.display !== 'none';
            clerkSignIn.style.display = isSignIn ? 'none' : 'block';
            clerkSignUp.style.display = isSignIn ? 'block' : 'none';
            clerkToggle.textContent = isSignIn
                ? '{{ __('Already have an account? Sign in') }}'
                : '{{ __('Need an account? Sign up') }}';
        });
    }
});
</script>
@endpush