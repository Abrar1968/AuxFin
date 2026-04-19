<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $appName = config('app.name', 'AuxFin');
            $canonicalUrl = url()->current();
            $description = 'AuxFin is an all-in-one financial operations platform for payroll, projects, accounting, and realtime business insights.';
            $logoUrl = asset('images/logo.jpg');
            $faviconUrl = asset('images/favicon.jpg');
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $appName }} | Financial Operations Platform</title>
        <meta name="description" content="{{ $description }}">
        <meta name="keywords" content="AuxFin, finance operations, payroll, accounting, ERP, project finance">
        <meta name="author" content="AuxFin">
        <meta name="application-name" content="{{ $appName }}">
        <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
        <meta name="theme-color" content="#0e7490">
        <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">

        <link rel="canonical" href="{{ $canonicalUrl }}">
        <link rel="icon" type="image/jpeg" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">

        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $appName }}">
        <meta property="og:title" content="{{ $appName }} | Financial Operations Platform">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ $logoUrl }}">
        <meta property="og:image:alt" content="{{ $appName }} logo">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $appName }} | Financial Operations Platform">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ $logoUrl }}">

        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $appName,
                'url' => $canonicalUrl,
                'logo' => $logoUrl,
            ], JSON_UNESCAPED_SLASHES) !!}
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
