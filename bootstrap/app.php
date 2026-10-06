<?php

use App\Http\Middleware\EnsureSigapAbility;
use App\Http\Middleware\EnsureSigapAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'sigap.auth' => EnsureSigapAuthenticated::class,
            'sigap.ability' => EnsureSigapAbility::class,
        ]);

        /*
         * Aplikasi berjalan di belakang edge Vercel, jadi alamat pengirim yang
         * dilihat PHP selalu alamat edge itu — sama untuk semua pengunjung.
         * Alamat asli pengunjung ada pada tajuk X-Forwarded-For, dan tajuk itu
         * baru dipercaya Laravel setelah proxy-nya didaftarkan di sini.
         *
         * Tanpa baris ini pembatas percobaan masuk kehilangan kemampuan
         * membedakan satu pengunjung dari pengunjung lain.
         *
         * Alamat proxy Vercel tidak tetap sehingga tidak dapat didaftarkan
         * satu per satu. Itu aman di sini karena seluruh permintaan wajib
         * melewati edge Vercel, dan edge menimpa X-Forwarded-For dengan alamat
         * sambungan yang sebenarnya. Meski begitu, pembatas percobaan masuk
         * tetap tidak bergantung pada alamat IP semata — lihat
         * LoginThrottleService.
         */
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
