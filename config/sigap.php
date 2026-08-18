<?php

return [

    'name' => env('APP_NAME', 'SIGAP Rudenim Surabaya'),

    'tagline' => 'Sistem Informasi & Gerakan Administratif Pengungsi',

    'institution' => [
        'short' => 'Rudenim Surabaya',
        'long' => 'Rumah Detensi Imigrasi Surabaya',
    ],

    'auth' => [
        'login_mode' => env('SIGAP_LOGIN_MODE', 'hybrid'),
        'demo_enabled' => (bool) env('SIGAP_DEMO_LOGIN_ENABLED', true),
        'laravel_auth_enabled' => (bool) env('SIGAP_LARAVEL_AUTH_ENABLED', true),
        'active_role_fallback' => env('SIGAP_ACTIVE_ROLE'),
    ],

    'data' => [
        'sample_data_enabled' => (bool) env('SIGAP_SAMPLE_DATA_ENABLED', true),
        'firebase_read_enabled' => (bool) env('SIGAP_FIREBASE_READ_ENABLED', true),
    ],

    'firebase' => [
        'database_url' => env('FIREBASE_DATABASE_URL', 'https://ralf-803d6-default-rtdb.asia-southeast1.firebasedatabase.app/'),
        'project_id' => 'ralf-803d6',
        'api_key' => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'ralf-803d6.firebaseapp.com'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'ralf-803d6.firebasestorage.app'),
        'storage_disk' => env('FIREBASE_STORAGE_DISK', 'local'),
        'storage_prefix' => env('FIREBASE_STORAGE_PREFIX', 'documents'),
        'storage_public_base_url' => env('FIREBASE_STORAGE_PUBLIC_BASE_URL'),
        'storage_bearer_token' => env('FIREBASE_STORAGE_BEARER_TOKEN'),

        /*
         * Service account dari Firebase Console -> Project Settings ->
         * Service accounts -> Generate new private key.
         *
         * Boleh diisi JSON apa adanya, JSON yang sudah di-base64, atau jalur
         * menuju berkas JSON. Dipakai untuk membuat access token berumur pendek
         * secara otomatis, menggantikan token tetap yang kedaluwarsa tiap jam.
         */
        'service_account' => env('FIREBASE_SERVICE_ACCOUNT_JSON'),
        'database_secret' => env('FIREBASE_DATABASE_SECRET'),
        'paths' => [
            'refugees' => '/refugees',
            'documents' => '/documents',
            'placements' => '/placements',
            'audit_trails' => '/audit_trails',
            'reports' => '/reports',
            'users' => '/users',
        ],
    ],

];
