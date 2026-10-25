<?php

namespace App\Http\Requests\Concerns;

/**
 * Membaca titik koordinat dari teks yang ditempel petugas.
 *
 * Dipakai bersama oleh formulir Penempatan dan formulir Data Pengungsi,
 * karena keduanya sama-sama mencatat tempat tinggal pengungsi mandiri.
 */
trait ReadsCoordinates
{
    /**
     * Alur yang dipakai di lapangan: buka Google Maps di titik rumah, tekan
     * lama, lalu salin. Yang tersalin bisa berupa sepasang angka, bisa pula
     * tautan peta. Keduanya diterima di sini supaya petugas tidak perlu
     * memisahkan lintang dan bujur sendiri.
     *
     * Tautan pendek maps.app.goo.gl tidak dapat dibaca karena titiknya baru
     * muncul setelah tautan itu diikuti; gunakan salin koordinat, bukan bagikan.
     *
     * @return array{0: string, 1: string}|null
     */
    protected function parseCoordinate(string $value): ?array
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Bentuk tautan Google Maps: .../@-7.348123,112.727456,17z
        if (preg_match('/@(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/', $value, $m)) {
            return [$m[1], $m[2]];
        }

        // Sepasang angka, baik ditempel apa adanya maupun dari parameter q=
        if (preg_match('/(-?\d{1,3}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/', $value, $m)) {
            return [$m[1], $m[2]];
        }

        return null;
    }

    protected function blankToNull(mixed $value): mixed
    {
        return trim((string) $value) === '' ? null : $value;
    }
}
