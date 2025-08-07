<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @routes
    @php
        $viteDevelopmentServer = false;
        // Solo intentar conectar al servidor de desarrollo si estamos en local
        if (app()->environment('local')) {
            // Comprobar si el servidor de desarrollo está en ejecución
            $host = env('VITE_HMR_HOST', 'localhost');
            $port = env('VITE_HMR_PORT', 5173);
            $viteDevelopmentServer = @fsockopen($host, $port) !== false;
        }
    @endphp

    @if ($viteDevelopmentServer)
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @else
        <!-- Usar assets compilados cuando el servidor de desarrollo no está disponible -->
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $cssFile = isset($manifest['resources/js/app.js']['css']) ? $manifest['resources/js/app.js']['css'][0] : '';
            $jsFile = isset($manifest['resources/js/app.js']['file']) ? $manifest['resources/js/app.js']['file'] : '';
        @endphp
        <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
        <script type="module" src="{{ asset('build/' . $jsFile) }}"></script>
    @endif
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
