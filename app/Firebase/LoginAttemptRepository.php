<?php

namespace App\Firebase;

/**
 * Catatan percobaan masuk yang gagal, dipakai untuk membatasi penebakan
 * kata sandi.
 *
 * Hitungannya disimpan di Firebase, bukan di cache Laravel. Di Vercel setiap
 * permintaan dilayani proses yang berdiri sendiri dan CACHE_STORE diisi
 * "array" — cache array hanya hidup selama satu permintaan, sehingga
 * middleware throttle bawaan Laravel selalu memulai hitungan dari nol dan
 * tidak membatasi apa pun. Firebase adalah satu-satunya tempat simpan yang
 * dibagi bersama oleh seluruh permintaan.
 *
 * Kunci setiap catatan berupa hash, sehingga email tidak tersimpan dalam
 * bentuk terbaca di dalam basis data.
 */
class LoginAttemptRepository extends Repository
{
    protected string $node = 'login_attempts';

    protected array $fields = [
        'attempts',
        'first_at',
        'locked_until',
    ];
}
