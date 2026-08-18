<?php

/**
 * Vercel serverless entrypoint — SIGAP Rudenim Surabaya.
 *
 * PENTING: di Vercel seluruh filesystem bersifat READ-ONLY kecuali /tmp.
 *
 * Laravel secara default menulis cache paket & service provider ke
 * bootstrap/cache/. Kalau folder itu tidak ada (atau read-only), maka
 * PackageManifest melempar:
 *
 *     "The .../bootstrap/cache directory must be present and writable."
 *
 * Kegagalan itu terjadi saat bootstrap RegisterFacades, sehingga seluruh
 * service provider inti (view, session, filesystem, ...) TIDAK jadi terdaftar.
 * Akibatnya Laravel gagal merender halaman error dan memunculkan:
 *
 *     "Target class [view] does not exist."  -> HTTP 500 halaman putih
 *
 * Solusinya: arahkan SEMUA path cache ke /tmp SEBELUM framework di-load.
 * Blok di bawah ini sengaja ditaruh paling atas dan tidak bergantung pada
 * keberadaan folder apa pun di dalam repositori.
 */

$cachePaths = [
    'APP_CONFIG_CACHE'   => '/tmp/config.php',
    'APP_EVENTS_CACHE'   => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE'   => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
    'DOMPDF_TEMP_DIR'    => '/tmp',
];

foreach ($cachePaths as $key => $value) {
    // Hormati nilai yang sudah diset di dashboard Vercel; hanya isi bila kosong.
    $current = getenv($key);

    if ($current === false || $current === '') {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Pastikan folder tujuan benar-benar ada sebelum Laravel menulis ke sana.
foreach (['/tmp/views', '/tmp/cache', '/tmp/sessions', '/tmp/logs', '/tmp/dompdf-fonts'] as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

require __DIR__ . '/../public/index.php';
