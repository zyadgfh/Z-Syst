<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="{{__('IE=edge')}}">
    <meta name="viewport" content="{{__('width=device-width, initial-scale=1.0')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title') @yield('title') | @endif Z-Syst</title>
    @include('layouts.partials.css')
    <link rel="stylesheet" href="{{ asset('assets/css/apple.css') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#f5f5f7">
</head>

<body class="ap-theme">
@yield('main_content')
@stack('modal')
@include('layouts.auth.partials.scripts')
<script src="{{ asset('assets/js/ap-theme.js') }}" defer></script>
<script src="{{ asset('assets/js/apple-motion.js') }}" defer></script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('{{ asset("sw.js") }}').catch(function () {});
        });
    }
</script>
</body>

</html>
