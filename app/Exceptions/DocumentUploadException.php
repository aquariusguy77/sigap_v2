<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika berkas dokumen gagal disimpan.
 *
 * Keberadaan kelas ini penting: sebelumnya kegagalan unggah tidak dilaporkan
 * sama sekali, sehingga sistem tetap mencatat jalur berkas seolah-olah
 * penyimpanan berhasil. Petugas melihat pesan sukses padahal berkasnya tidak
 * ada di mana pun. Sekarang kegagalan selalu sampai ke pengguna.
 */
class DocumentUploadException extends RuntimeException
{
    public static function bucketBelumDisetel(): self
    {
        return new self(
            'Penyimpanan Firebase belum dikonfigurasi. ' .
            'Isi FIREBASE_STORAGE_BUCKET terlebih dahulu.'
        );
    }

    public static function kredensialBelumDisetel(): self
    {
        return new self(
            'Kredensial Firebase Storage belum tersedia. Isi FIREBASE_SERVICE_ACCOUNT_JSON ' .
            'dengan berkas service account dari Firebase Console, atau ubah ' .
            'FIREBASE_STORAGE_DISK menjadi "local" untuk menyimpan berkas secara lokal.'
        );
    }

    public static function tokenGagal(): self
    {
        return new self(
            'Gagal memperoleh izin akses ke Firebase Storage. ' .
            'Periksa kembali isi service account dan pastikan waktu server sudah tepat.'
        );
    }

    public static function unggahGagal(string $detail = ''): self
    {
        return new self(trim('Berkas gagal diunggah ke Firebase Storage. ' . $detail));
    }

    public static function penyimpananLokalGagal(): self
    {
        return new self(
            'Berkas gagal disimpan ke penyimpanan lokal. Pada lingkungan hosting ' .
            'yang bersifat baca-saja, gunakan FIREBASE_STORAGE_DISK=rtdb agar ' .
            'berkas tersimpan di Realtime Database, atau firebase-rest bila ' .
            'Firebase Storage sudah diaktifkan.'
        );
    }

    public static function berkasTerlaluBesar(int $sizeKb, int $maxKb): self
    {
        return new self(sprintf(
            'Ukuran berkas %s MB melebihi batas %s MB untuk penyimpanan di ' .
            'Realtime Database. Perkecil berkasnya, atau naikkan ' .
            'FIREBASE_STORAGE_RTDB_MAX_KB bila memang diperlukan.',
            number_format($sizeKb / 1024, 1, ',', '.'),
            number_format($maxKb / 1024, 1, ',', '.')
        ));
    }

    public static function berkasTidakTerbaca(): self
    {
        return new self('Berkas unggahan tidak dapat dibaca. Coba unggah ulang.');
    }

    public static function realtimeDatabaseGagal(string $detail = ''): self
    {
        return new self(trim(
            'Berkas gagal disimpan ke Realtime Database. ' .
            'Periksa FIREBASE_DATABASE_URL, kredensial service account, dan ' .
            'aturan keamanan Realtime Database. ' . $detail
        ));
    }
}
