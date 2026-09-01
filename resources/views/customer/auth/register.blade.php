@extends('layouts.auth.app')

@section('title', __('Create Account'))

@section('main_content')
<div class="min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0f9ff 100%);">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-2xl font-bold" style="color: #15803d;">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                    <rect width="40" height="40" rx="10" fill="#15803d"/>
                    <path d="M12 20h16M20 12v16" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                Z-Syst
            </a>
            <p class="mt-2 text-sm" style="color: #6b7280;">{{ __('Create your customer account') }}</p>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-2xl shadow-lg p-8" style="backdrop-filter: blur(20px);">
            <h1 class="text-xl font-semibold text-center mb-6" style="color: #111827;">{{ __('Sign Up') }}</h1>

            @if ($errors->any())
                <div class="mb-4 p-3 rounded-lg text-sm" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('customer.register.store') }}" class="space-y-4">
                @csrf

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Full Name') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 transition-all"
                        style="border-color: #e5e7eb; focus-ring-color: #15803d;"
                        placeholder="{{ __('Enter your full name') }}">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Email Address') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 transition-all"
                        style="border-color: #e5e7eb;"
                        placeholder="{{ __('you@example.com') }}">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Phone Number') }}</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required
                        class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 transition-all"
                        style="border-color: #e5e7eb;"
                        placeholder="{{ __('01XXXXXXXXX') }}">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Password') }}</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 transition-all"
                        style="border-color: #e5e7eb;"
                        placeholder="{{ __('Min. 6 characters') }}">
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Confirm Password') }}</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 transition-all"
                        style="border-color: #e5e7eb;"
                        placeholder="{{ __('Repeat your password') }}">
                </div>

                <!-- Submit -->
                <button type="submit"
                    class="w-full py-3 rounded-xl text-white font-semibold text-sm transition-all"
                    style="background: #15803d; hover: background: #166534; transform: scale(1);"
                    onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
                    {{ __('Create Account') }}
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center my-6">
                <div class="flex-1 border-t" style="border-color: #e5e7eb;"></div>
                <span class="px-3 text-xs" style="color: #9ca3af;">{{ __('or') }}</span>
                <div class="flex-1 border-t" style="border-color: #e5e7eb;"></div>
            </div>

            <!-- Login Link -->
            <p class="text-center text-sm" style="color: #6b7280;">
                {{ __('Already have an account?') }}
                <a href="{{ route('customer.login') }}" class="font-semibold" style="color: #15803d; text-decoration: none;">
                    {{ __('Sign in') }}
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
