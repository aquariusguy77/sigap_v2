<?php

namespace App\Session;

use App\Services\FirebaseRealtimeDatabaseService;
use Illuminate\Support\Carbon;
use SessionHandlerInterface;

/**
 * Penyimpanan sesi pada Firebase Realtime Database.
 *
 * Driver "cookie" bawaan Laravel menaruh seluruh data sesi di dalam cookie
 * peramban, tanpa salinan di sisi server. Akibatnya logout hanya menghapus
 * cookie pada peramban pengguna dan tidak dapat membatalkan cookie yang sudah
 * terlanjur disalin — sesi lama tetap sah sampai masa berlakunya habis.
 *
 * Handler ini memindahkan data sesi ke node "sessions" di Firebase. Cookie
 * pada peramban cukup menyimpan ID sesi. Saat logout, invalidate() memanggil
 * destroy() yang menghapus node tersebut, sehingga ID sesi lama tidak lagi
 * menunjuk ke data apa pun dan upaya memakai ulang cookie lama gagal.
 *
 * Node "sessions" hanya dapat diakses lewat service account aplikasi, sehingga
 * aturan tolak-bawaan Firebase menjaganya tetap tertutup dari publik.
 */
class FirebaseSessionHandler implements SessionHandlerInterface
{
    public function __construct(
        protected FirebaseRealtimeDatabaseService $firebase,
        protected int $minutes
    ) {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * Membaca data sesi. Mengembalikan string kosong bila node tidak ada atau
     * sudah melewati masa aktif; keduanya diperlakukan sebagai sesi yang tidak
     * berlaku sehingga Laravel memulai sesi baru.
     */
    public function read(string $id): string
    {
        $record = $this->firebase->fetchNode($this->sessionPath($id));

        if (! is_array($record) || ! isset($record['payload'])) {
            return '';
        }

        if ($this->kedaluwarsa($record)) {
            return '';
        }

        $payload = base64_decode((string) $record['payload'], true);

        return $payload === false ? '' : $payload;
    }

    /**
     * Menyimpan data sesi. Isi sesi dibungkus base64 karena hasil serialisasi
     * PHP dapat memuat bita yang bukan UTF-8, sedangkan Firebase hanya
     * menerima teks JSON yang sah.
     */
    public function write(string $id, string $data): bool
    {
        return $this->firebase->putNode($this->sessionPath($id), [
            'payload' => base64_encode($data),
            'last_activity' => Carbon::now()->getTimestamp(),
        ]);
    }

    public function destroy(string $id): bool
    {
        return $this->firebase->deleteNode($this->sessionPath($id));
    }

    /**
     * Membersihkan sesi yang sudah lama tidak aktif. Dipanggil Laravel secara
     * acak (lihat lottery pada config/session.php). Jumlah akun aplikasi ini
     * sedikit sehingga membaca seluruh node sesi tetap murah.
     */
    public function gc(int $max_lifetime): int
    {
        $all = $this->firebase->fetchNode($this->firebase->path('sessions'));

        if (! is_array($all)) {
            return 0;
        }

        $batas = Carbon::now()->getTimestamp() - $max_lifetime;
        $dihapus = 0;

        foreach ($all as $id => $record) {
            $terakhir = is_array($record) ? (int) ($record['last_activity'] ?? 0) : 0;

            if ($terakhir < $batas && $this->firebase->deleteNode($this->sessionPath((string) $id))) {
                $dihapus++;
            }
        }

        return $dihapus;
    }

    protected function sessionPath(string $id): string
    {
        return $this->firebase->path('sessions') . '/' . $id;
    }

    protected function kedaluwarsa(array $record): bool
    {
        if ($this->minutes <= 0) {
            return false;
        }

        $terakhir = (int) ($record['last_activity'] ?? 0);

        if ($terakhir <= 0) {
            return false;
        }

        return $terakhir < Carbon::now()->subMinutes($this->minutes)->getTimestamp();
    }
}
