<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>
        @hasSection('title')
            @yield('title') |
        @endif {{ get_option('general')['title'] ?? config('app.name') }}
    </title>
    @include('landing::layouts.web.partials.css')
</head>

<body>

    @include('landing::layouts.web.partials.header')
    
    @yield('main_content')

    @include('landing::layouts.web.partials.footer')

    @include('landing::layouts.web.partials.script')
</body>

</html>
