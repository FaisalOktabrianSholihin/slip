<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'E-Slip Gaji Mitratani')</title>

    <link rel="icon" href="{{ asset('assets/logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons: dipakai sidebar.js & topheader.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/topheader.css') }}">

    @stack('styles')
</head>

<body @if (Route::currentRouteName()) data-nav="{{ Route::currentRouteName() }}" @endif>

    <!-- WAJIB: sidebar.js hanya berjalan jika .app-container dan #sidebar-container ada -->
    <div class="app-container">

        <div id="sidebar-container">
            @include('partials.sidebar')
        </div>

        <main class="main-content">

            <div id="topheader-container" data-title="@yield('page-title', 'Halaman')" data-subtitle="@yield('page-subtitle', '')"
                data-icon="@yield('page-icon', '')" data-user-name="Administrator" data-user-role="Admin SDM">
                @include('partials.topheader')
            </div>

            @yield('content')

        </main>

    </div><!-- /.app-container -->

    @yield('modals')

    <script src="{{ asset('js/ui-dialog.js') }}"></script>
    <script src="{{ asset('js/api-client.js') }}"></script>
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <script src="{{ asset('js/topheader.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
    @stack('scripts')
</body>

</html>
