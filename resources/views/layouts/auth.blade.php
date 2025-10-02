<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asset Management - LRT JAKARTA</title>
    <link rel="shortcut icon" href="{{ asset('metronic/demo1/assets/media/logos/logo-lrtj-icon-color-large.png') }}" />
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>

    {{-- Metronic Demo1 CSS --}}
    <link rel="stylesheet" href="{{ asset('metronic/demo1/assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('metronic/demo1/assets/css/style.bundle.css') }}">
    <style>
        body {
            font-family: 'Montserrat';
            font-size: 22px;
        }
    </style>
    @stack('head')
</head>

<body class="app-blank">

    {{-- Centered auth container --}}
    <div class="d-flex flex-center flex-column flex-column-fluid p-10">
        @yield('content')
    </div>

    {{-- Metronic Demo1 JS --}}
    <script src="{{ asset('metronic/demo1/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/js/scripts.bundle.js') }}"></script>
    @stack('scripts')
</body>

</html>
