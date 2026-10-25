<?php

namespace App\Http\Requests\Concerns;

use App\Services\GoogleMapsLinkResolver;

/**
 * Membaca titik koordinat dari teks yang ditempel petugas.
 *
 * Dipakai bersama oleh formulir Penempatan dan formulir Data Pengungsi,
 * karena keduanya sama-sama mencatat tempat tinggal pengungsi mandiri.
 */
trait ReadsCoordinates
{
    /**
     * Alur yang dipakai di lapangan: buka Google Maps di titik rumah, lalu
     * salin koordinatnya atau bagikan tautannya. Sepasang angka, tautan
     * Google Maps biasa, maupun tautan bagikan maps.app.goo.gl dari HP
     * semuanya diterima; rinciannya ada di GoogleMapsLinkResolver.
     *
     * @return array{0: string, 1: string}|null
     */
    protected function parseCoordinate(string $value): ?array
    {
        return app(GoogleMapsLinkResolver::class)->coordinates($value);
    }

    protected function blankToNull(mixed $value): mixed
    {
        return trim((string) $value) === '' ? null : $value;
    }
}
