<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Asset Management - LRT JAKARTA</title>
    <link rel="shortcut icon" href="{{ asset('metronic/demo1/assets/media/logos/logo-lrtj-icon-color-large.png') }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link href="{{ asset('metronic/demo1/assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('metronic/demo1/assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet"
        type="text/css" />

    {{-- Metronic Demo1 CSS from /public --}}
    <link rel="stylesheet" href="{{ asset('metronic/demo1/assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('metronic/demo1/assets/css/style.bundle.css') }}">
    <style>
        body {
            font-family: 'Montserrat';
            font-size: 22px;
        }
        th {
            font-weight: bold !important;
        }
    </style>

    @stack('head')
</head>

<body id="kt_app_body" data-kt-app-layout="light-sidebar" data-kt-app-header-fixed="true"
    data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            @includeIf('partials.header')

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                @includeIf('partials.sidebar')

                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        @yield('content')
                    </div>

                    @includeIf('partials.footer')
                </div>
            </div>
        </div>
    </div>

    {{-- Metronic Demo1 JS from /public --}}
    <script src="{{ asset('metronic/demo1/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/js/widgets.bundle.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/continentsLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/usaLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZonesLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZoneAreasLow.js"></script>
    <script src="{{ asset('metronic/demo1/assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/js/custom/widgets.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/js/custom/apps/chat/chat.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/js/custom/utilities/modals/create-app.js') }}"></script>
    <script src="{{ asset('metronic/demo1/assets/js/custom/utilities/modals/users-search.js') }}"></script>
    @stack('scripts')
</body>

</html>
