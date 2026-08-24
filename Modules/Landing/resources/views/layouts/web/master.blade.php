@php
    $seoTitle = $seo_title ?? (get_option('general')['title'] ?? 'Z-Syst');
    $seoDescription = $seo_description ?? __('Smart pharmacy management platform. Manage inventory, sales, prescriptions, and operations with accuracy, speed, and full control.');
    $seoImage = $seo_image ?? asset(get_option('general')['favicon'] ?? 'assets/images/logo/logo.png');
    $seoUrl = request()->url();
    $seoKeywords = $seo_keywords ?? __('pharmacy management, pharmacy software, inventory management, point of sale, POS, prescription management, pharmacy system');
    $currentLocale = app()->getLocale();
    $isRtl = $currentLocale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    {{-- SEO Meta Tags --}}
    <title>
        @hasSection('title')
            @yield('title') |
        @endif {{ $seoTitle }}
    </title>
    <meta name="description" content="{{ Str::limit(strip_tags($seoDescription), 160, '') }}" />
    <meta name="keywords" content="{{ $seoKeywords }}" />
    <meta name="author" content="{{ $seoTitle }}" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
    <meta name="googlebot" content="index, follow" />
    <link rel="canonical" href="{{ $seoUrl }}" />

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ $seoUrl }}" />
    <meta property="og:title" content="{{ $seoTitle }}" />
    <meta property="og:description" content="{{ Str::limit(strip_tags($seoDescription), 200, '') }}" />
    <meta property="og:image" content="{{ $seoImage }}" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:site_name" content="{{ $seoTitle }}" />
    <meta property="og:locale" content="{{ $isRtl ? 'ar_EG' : 'en_US' }}" />
    <meta property="og:locale:alternate" content="{{ $isRtl ? 'en_US' : 'ar_EG' }}" />

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $seoTitle }}" />
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($seoDescription), 200, '') }}" />
    <meta name="twitter:image" content="{{ $seoImage }}" />
n    {{-- Alternate language versions for SEO --}}
    <link rel="alternate" hreflang="en" href="{{ url('/') }}?lang=en" />
    <link rel="alternate" hreflang="ar" href="{{ url('/') }}?lang=ar" />
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}" />
n    @include('landing::layouts.web.partials.css')
</head>

<body>

    @include('landing::layouts.web.partials.header')

    @yield('main_content')

    @include('landing::layouts.web.partials.footer')

    @include('landing::layouts.web.partials.script')
</body>

</html>
