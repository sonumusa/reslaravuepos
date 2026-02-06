<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ResLaraVuePOS') }}</title>
    
    <!-- PWA -->
    <meta name="theme-color" content="#3B82F6">
    <link rel="manifest" href="/build/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/pwa-icon.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div id="app"></div>
</body>
</html>
