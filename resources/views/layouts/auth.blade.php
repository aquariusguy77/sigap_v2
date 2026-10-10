<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#125f72">
    <title>Masuk • SIGAP Rudenim Surabaya</title>
    <meta name="description" content="SIGAP — Sistem Informasi & Gerakan Administratif Pengungsi Rudenim Surabaya.">
    <link rel="icon" type="image/png" href="{{ config('branding.logo_url') . '?v=' . config('branding.asset_version') }}">
    <link rel="apple-touch-icon" href="{{ config('branding.logo_url') . '?v=' . config('branding.asset_version') }}">
    @include('sigap.partials.styles')
    @include('sigap.partials.auth-styles')
</head>
<body class="auth-body">
    @yield('content')
</body>
</html>
