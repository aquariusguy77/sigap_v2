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
            'yang bersifat baca-saja, gunakan FIREBASE_STORAGE_DISK=firebase-rest.'
        );
    }
}
