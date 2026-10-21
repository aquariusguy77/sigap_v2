<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Firebase tidak dapat dibaca: jaringan terputus, waktu tunggu habis, atau
 * Firebase menjawab dengan galat.
 *
 * Dibedakan dari node yang memang kosong. Sebelumnya kedua keadaan itu sama-
 * sama menghasilkan daftar kosong, sehingga gangguan sambungan tampil sebagai
 * "tidak ada data" dan petugas dapat mengira datanya hilang. Pengecualian ini
 * ditangkap di bootstrap/app.php dan ditampilkan sebagai halaman
 * "Data gagal dimuat".
 */
class FirebaseReadException extends RuntimeException
{
    public static function gagalMembaca(?string $alasan): self
    {
        return new self('Data gagal dimuat dari Firebase' . ($alasan ? ': ' . $alasan : '.'));
    }
}
