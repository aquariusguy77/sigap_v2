<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan tajuk keamanan HTTP pada setiap respons.
 *
 * Pemindaian keamanan eksternal menemukan bahwa respons aplikasi belum
 * menyertakan tajuk keamanan yang lazim (OWASP A05:2025 Security
 * Misconfiguration). Middleware ini menutup temuan tersebut.
 *
 * Content-Security-Policy sengaja dibatasi pada arahan yang tidak memengaruhi
 * pemuatan sumber daya (frame-ancestors, base-uri, form-action, object-src)
 * agar tidak memutus skrip maupun gaya sebaris serta pustaka peta yang sudah
 * dipakai aplikasi. Arahan frame-ancestors 'none' memberi perlindungan
 * clickjacking yang setara dengan X-Frame-Options.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            /*
             * geolocation=(self): tombol "Pakai lokasi saya sekarang" pada
             * pemilih titik rumah pengungsi mandiri. Tetap tertutup bagi
             * situs lain; kamera dan mikrofon tetap dimatikan.
             */
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self)',
            'Content-Security-Policy' => "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'",
        ];

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // Menyembunyikan versi PHP yang bocor lewat tajuk X-Powered-By.
        $response->headers->remove('X-Powered-By');

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
