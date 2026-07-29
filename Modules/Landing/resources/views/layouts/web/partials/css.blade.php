<link rel="shortcut icon" type="image/x-icon" href="{{ asset(get_option('general')['favicon'] ?? 'assets/images/logo/favicon.png')}}">
<link rel="stylesheet" href="{{ asset('assets/web/css/bootstrap.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/swiper-bundle.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/fonts/fontawesome/css/all.min.css') }}" />
<!-- Slick Slider -->
<link rel="stylesheet" href="{{ asset('assets/web/css/slick.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/slick-theme.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/aos.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/toastr.min.css') }}">
<!-- Custom Css -->
<link rel="stylesheet" href="{{ asset('assets/web/css/styles.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/web/css/responsive.css') }}" />

@stack('css')
