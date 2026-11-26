<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <meta
        name="theme-color"
        content="#1e3a8a"
    >
    <meta
        name="mobile-web-app-capable"
        content="yes"
    >
    <meta
        name="apple-mobile-web-app-capable"
        content="yes"
    >
    <meta
        name="apple-mobile-web-app-title"
        content="eLogBook"
    >
    <link
        rel="manifest"
        href="{{ asset('manifest.webmanifest') }}"
    >
    <link
        rel="icon"
        href="{{ asset('pwa/icon-192.svg') }}"
        type="image/svg+xml"
    >
    <title>UiTM E-Logbook Portal</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >
</head>

<body
    class="@yield('body_class', 'p-6') min-h-screen bg-gradient-to-b from-blue-900 to-indigo-950 text-slate-800 antialiased"
>
    @yield('content')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js').catch(() => {});
            });
        }
    </script>
</body>

</html>
