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
    @include('layouts.partials.css')
    <script>
        // Dark mode initialization
        if (localStorage.getItem('darkMode') === 'true' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="{{ session('dark_mode') ? 'dark:bg-gray-900 dark:text-white' : 'bg-white text-gray-900' }}">

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

@include('layouts.partials.script')
</body>
</html>
