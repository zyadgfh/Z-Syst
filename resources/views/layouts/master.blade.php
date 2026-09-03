<!DOCTYPE html>
@if (app()->getLocale() == 'ar')
<html lang="ar" dir="rtl" class="{{ session('dark_mode') ? 'dark' : '' }}">
@else
<html lang="en" dir="auto" class="{{ session('dark_mode') ? 'dark' : '' }}">
@endif
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="{{__('IE=edge')}}">
    <meta name="viewport" content="{{__('width=device-width, initial-scale=1.0')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title') @yield('title') | @endif {{ get_option('general')['title'] ?? config('app.name') }}</title>
    
    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    
    {{-- Vite compiled assets (when built) --}}
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    {{-- Admin CSS loaded conditionally via @push('admin_css') in admin layouts --}}
    @stack('admin_css')
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @include('layouts.partials.css')
    @include('layouts.partials.clerk')
    
    <script>
        // Dark mode initialization
        if (localStorage.getItem('darkMode') === 'true' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // Apply CSS custom properties from data-*-style attributes
        // Enables data-bar-height data-bar-style="--bar-h: 120" pattern
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-bar-style]').forEach(function(el) {
                el.style.cssText += ';' + el.getAttribute('data-bar-style');
            });
            document.querySelectorAll('[data-progress-style]').forEach(function(el) {
                el.style.cssText += ';' + el.getAttribute('data-progress-style');
            });
            document.querySelectorAll('[data-dot-style]').forEach(function(el) {
                el.style.cssText += ';' + el.getAttribute('data-dot-style');
            });
        });
    </script>
</head>
<body class="{{ session('dark_mode') ? 'dark:bg-gray-900 dark:text-white' : 'bg-white text-gray-900' }}" style="background-color: var(--color-background); color: var(--color-foreground);">

<!-- Side Bar Start -->
@include('layouts.partials.side-bar')
<!-- Side Bar End -->
<div class="section-container">
    <!-- header start -->
    @include('layouts.partials.header')
    <!-- header end -->
    <!-- erp-state-overview-section start -->
    @yield('main_content')
    @include('layouts.partials.footer')
    <!-- erp-state-overview-section end -->
    @stack('modal')
</div>

@include('layouts.partials.flash-messages')
@include('layouts.partials.script')

{{-- PWA Service Worker Registration --}}
@if(env('APP_ENV') === 'production')
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
</script>
@endif
</body>
</html>
