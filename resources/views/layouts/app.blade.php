<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>AgroIot</title>

    <link rel="icon" type="image/x-icon" href="images/favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>

    <link rel="stylesheet" href="{{ asset('assets/vendor/animation/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/simplebar/simplebar.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/Fonts/bold/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/Fonts/duotone/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/Fonts/fill/style.css') }}">
    
    <link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400..900&display=swap" rel="stylesheet">

    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="light">
    <script src="{{ asset('assets/js/storage.js') }}"></script>

    <div class="app-wrapper">
        @include('layouts.sidebar')

        <div class="app-content">
            @include('layouts.navigation', ['headerTitle' => $headerTitle ?? 'Panel de Control'])
            
            <main class="app-content-area"> 
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery-3.6.3.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/simplebar/simplebar.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script src="{{ asset('assets/js/toggle-onoff.js') }}"></script>

    @stack('scripts')
</body>
</html>