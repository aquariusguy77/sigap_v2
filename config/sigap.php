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

    /*
     * Data acuan operasional Rudenim Surabaya.
     *
     * Dikumpulkan di satu tempat agar dapat disesuaikan tanpa menyentuh kode
     * controller maupun tampilan.
     */
    'reference' => [

        /*
         * Berkas yang wajib dikumpulkan setiap pengungsi. Hanya dua, sesuai
         * ketentuan yang berlaku di Rudenim Surabaya.
         */
        'document_types' => [
            'Kartu Pengungsi',
            'Kartu Wajib Lapor',
        ],

        /*
         * Penempatan terbagi dua.
         *
         *  iom      Pengungsi yang memperoleh fasilitas dari IOM. Seluruhnya
         *           terpusat di Community House, jadi lokasinya cukup dipilih
         *           dari daftar.
         *  mandiri  Pengungsi yang mencari tempat tinggal sendiri. Alamatnya
         *           berbeda-beda dan perlu dicatat lengkap agar petugas dapat
         *           menemukannya saat pengawasan lapangan.
         */
        'placement_categories' => [
            'iom' => [
                'label' => 'Fasilitas IOM',
                'description' => 'Ditempatkan di Community House yang disediakan IOM.',
            ],
            'mandiri' => [
                'label' => 'Mandiri',
                'description' => 'Mencari dan membiayai tempat tinggal sendiri.',
            ],
        ],

        'community_houses' => [
            'CH Puspa Agro',
            'CH Green Bamboo',
        ],

        /*
         * Status data pengungsi. Hanya dua yang dipakai sehari-hari: yang
         * datanya sudah beres, dan yang masih menunggu pemeriksaan supervisor.
         */
        'refugee_statuses' => [
            'Aktif',
            'Perlu Verifikasi',
        ],

        /*
         * Lokasi hunian pada data pengungsi.
         *
         * Dua Community House disebut namanya, sedangkan pengungsi yang
         * mencari tempat tinggal sendiri cukup ditandai "Pengungsi Mandiri".
         * Alamat rincinya dicatat pada menu Penempatan, bukan di sini, karena
         * di sanalah tersedia kolom alamat beserta koordinat dan petunjuk arah.
         */
        'refugee_locations' => [
            'CH Puspa Agro',
            'CH Green Bamboo',
            'Pengungsi Mandiri',
        ],
    ],

    'firebase' => [
        'database_url' => env('FIREBASE_DATABASE_URL', 'https://ralf-803d6-default-rtdb.asia-southeast1.firebasedatabase.app/'),
        'project_id' => 'ralf-803d6',
        'api_key' => env('FIREBASE_API_KEY'),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'ralf-803d6.firebaseapp.com'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'ralf-803d6.firebasestorage.app'),
        'storage_disk' => env('FIREBASE_STORAGE_DISK', 'local'),
        'storage_prefix' => env('FIREBASE_STORAGE_PREFIX', 'documents'),

        /*
         * Penyimpanan berkas di dalam Realtime Database (mode "rtdb").
         *
         * Firebase Storage mensyaratkan paket Blaze, sedangkan Realtime
         * Database sudah tersedia sejak paket Spark. Pada mode ini isi berkas
         * disimpan sebagai base64 di node tersendiri, terpisah dari metadata
         * dokumen, supaya daftar dokumen tetap ringan dibaca.
         */
        'storage_rtdb_node' => env('FIREBASE_STORAGE_RTDB_NODE', 'document_files'),
        'storage_rtdb_max_kb' => (int) env('FIREBASE_STORAGE_RTDB_MAX_KB', 5120),
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
