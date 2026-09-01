<!DOCTYPE html>
@if (app()->getLocale() == 'ar')
<html lang="ar" dir="rtl">
@else
<html lang="en" dir="auto">
@endif
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="{{__('IE=edge')}}">
    <meta name="viewport" content="{{__('width=device-width, initial-scale=1.0')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title') @yield('title') | @endif Z-Syst</title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @include('layouts.partials.css')
    
    @stack('css')
    @include('layouts.partials.clerk')
</head>

<body>
@yield('main_content')
@stack('modal')
@include('layouts.auth.partials.scripts')
@stack('script')
<script>
    // Clerk auth controls for the login page
    window.addEventListener('load', function () {
        var clerkMounted = null;
        
        function mountSignIn() {
            if (typeof Clerk === 'undefined' || !Clerk.loaded) return;
            var el = document.getElementById('clerk-sign-in');
            if (el) {
                Clerk.mountSignIn(el);
                clerkMounted = 'signin';
            }
        }
        
        function mountSignUp() {
            if (typeof Clerk === 'undefined' || !Clerk.loaded) return;
            var el = document.getElementById('clerk-sign-up');
            if (el) {
                Clerk.mountSignUp(el);
                clerkMounted = 'signup';
            }
        }
        
        // Retry until Clerk is ready, then mount sign-in
        var interval = setInterval(function () {
            if (typeof Clerk !== 'undefined' && Clerk.loaded) {
                clearInterval(interval);
                mountSignIn();
            }
        }, 100);
        setTimeout(function () { clearInterval(interval); }, 5000);
        
        // Toggle between sign-in and sign-up
        var clerkToggle = document.getElementById('clerk-toggle');
        if (clerkToggle) {
            clerkToggle.addEventListener('click', function() {
                var signInEl = document.getElementById('clerk-sign-in');
                var signUpEl = document.getElementById('clerk-sign-up');
                var showingSignIn = signInEl && signInEl.style.display !== 'none';
                
                if (showingSignIn) {
                    signInEl.style.display = 'none';
                    signUpEl.style.display = 'block';
                    clerkToggle.textContent = '{{ __('Already have an account? Sign in') }}';
                    if (clerkMounted !== 'signup') {
                        mountSignUp();
                    }
                } else {
                    signInEl.style.display = 'block';
                    signUpEl.style.display = 'none';
                    clerkToggle.textContent = '{{ __('Need an account? Sign up') }}';
                    if (clerkMounted !== 'signin') {
                        mountSignIn();
                    }
                }
            });
        }
    });
</script>
</body>

</html>
