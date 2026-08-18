<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika penyimpanan ke Firebase Realtime Database gagal.
 *
 * Kegagalan tulis tidak boleh berlalu diam-diam: petugas harus tahu bahwa
 * datanya belum tersimpan, lengkap dengan petunjuk penyebabnya.
 */
class FirebaseWriteException extends RuntimeException
{
    public static function gagalMenyimpan(?string $detail = null): self
    {
        return new self(self::pesan('Data gagal disimpan ke Firebase.', $detail));
    }

    public static function gagalMenghapus(?string $detail = null): self
    {
        return new self(self::pesan('Data gagal dihapus dari Firebase.', $detail));
    }

    protected static function pesan(string $ringkas, ?string $detail): string
    {
        $petunjuk = 'Periksa FIREBASE_DATABASE_URL, kredensial service account, '
            . 'dan aturan keamanan Realtime Database.';

        return trim($ringkas . ' ' . $petunjuk . ($detail ? ' (' . $detail . ')' : ''));
    }
}
