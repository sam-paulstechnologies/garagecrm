<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Garage CRM') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @routes
    @viteReactRefresh
    @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
    @inertiaHead

    <!-- Loader styles -->
    <style>
        #app-loader {
            position: fixed;
            inset: 0;
            background: #f8fafc;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity .3s ease, visibility .3s ease;
        }

        #app-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .car {
            width: 120px;
            height: 60px;
            position: relative;
        }

        .car-body {
            background: #2563eb;
            width: 120px;
            height: 34px;
            border-radius: 6px;
            position: absolute;
            top: 14px;
        }

        .car-top {
            background: #2563eb;
            width: 60px;
            height: 20px;
            position: absolute;
            top: 0;
            left: 30px;
            border-radius: 6px 6px 0 0;
        }

        .wheel {
            width: 18px;
            height: 18px;
            background: #111827;
            border-radius: 50%;
            position: absolute;
            bottom: -4px;
            animation: spin .8s linear infinite;
        }

        .wheel.left { left: 20px; }
        .wheel.right { right: 20px; }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="font-sans antialiased">

    <!-- 🚗 GLOBAL LOADER -->
    <div id="app-loader">
        <div class="car">
            <div class="car-top"></div>
            <div class="car-body"></div>
            <div class="wheel left"></div>
            <div class="wheel right"></div>
        </div>
    </div>

    @inertia

</body>
</html>
