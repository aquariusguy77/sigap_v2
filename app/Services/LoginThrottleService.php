<?php

namespace App\Services;

use App\Firebase\LoginAttemptRepository;
use App\Firebase\Record;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Membatasi jumlah percobaan masuk yang gagal.
 *
 * Dua batas dipakai bersamaan, masing-masing dengan alasannya sendiri:
 *
 *  1. per_account — satu akun dari satu alamat IP. Batas inilah yang
 *     menghentikan penebakan kata sandi dalam keadaan biasa, dan ambangnya
 *     dibuat rendah.
 *
 *  2. per_email — satu akun dari alamat IP mana pun. Diperlukan karena
 *     batas pertama masih dapat dihindari dengan berpindah-pindah alamat,
 *     dan di belakang proxy alamat IP berasal dari tajuk X-Forwarded-For
 *     yang pada prinsipnya dapat dipalsukan. Ambangnya lebih tinggi supaya
 *     akun tidak mudah dikunci oleh orang lain dengan sengaja.
 *
 * Tidak ada batas "per alamat IP saja". Di Vercel seluruh permintaan masuk
 * melalui edge yang sama, jadi batas semacam itu mudah berubah menjadi satu
 * penghitung bersama yang mengunci semua petugas sekaligus.
 *
 * Bila Firebase sedang tidak dapat dihubungi, pembatas ini mengalah dan
 * membiarkan percobaan berjalan. Itu tidak menambah risiko: pemeriksaan kata
 * sandi juga membaca akun dari Firebase, sehingga saat Firebase mati tidak
 * ada seorang pun yang bisa masuk.
 */
class LoginThrottleService
{
    public function __construct(
        protected LoginAttemptRepository $attempts
    ) {
    }

    /**
     * Sisa waktu terkunci dalam detik. Nol berarti percobaan boleh berjalan.
     */
    public function lockedFor(string $email, ?string $ip): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $sisa = 0;

        foreach ($this->limits($email, $ip) as $limit) {
            try {
                $catatan = $this->attempts->find($limit['key']);
            } catch (Throwable) {
                continue;
            }

            $sisa = max($sisa, $this->remainingLock($catatan));
        }

        return $sisa;
    }

    /**
     * Mencatat satu percobaan yang gagal, dan mengunci bila sudah melewati
     * ambang batas.
     */
    public function recordFailure(string $email, ?string $ip): void
    {
        if (! $this->enabled()) {
            return;
        }

        $sekarang = Carbon::now();

        foreach ($this->limits($email, $ip) as $limit) {
            try {
                $catatan = $this->attempts->find($limit['key']);

                /*
                 * Jendela yang sudah habis dianggap lembaran baru, supaya
                 * kegagalan sesekali karena salah ketik tidak menumpuk
                 * selamanya menjadi penguncian.
                 */
                $jumlah = $this->withinWindow($catatan, $limit['window'], $sekarang)
                    ? ((int) ($catatan->attempts ?? 0)) + 1
                    : 1;

                $mulai = $jumlah === 1
                    ? $sekarang
                    : Carbon::parse((string) $catatan->first_at);

                $terkunciSampai = $jumlah >= $limit['max']
                    ? $sekarang->copy()->addSeconds($limit['lock'])
                    : null;

                /*
                 * Penguncian yang sedang berjalan tidak boleh diperpanjang
                 * oleh percobaan baru, agar penyerang tidak dapat menahan
                 * sebuah akun tetap terkunci tanpa batas waktu.
                 */
                $terkunciLama = $this->lockedUntil($catatan);

                if ($terkunciLama && $terkunciLama->greaterThan($sekarang)) {
                    $terkunciSampai = $terkunciLama;
                }

                $this->attempts->put($limit['key'], [
                    'attempts' => $jumlah,
                    'first_at' => $mulai->toIso8601String(),
                    'locked_until' => $terkunciSampai?->toIso8601String(),
                ]);
            } catch (Throwable) {
                // Gagal mencatat tidak boleh menggagalkan tanggapan halaman masuk.
            }
        }

        $this->pruneSometimes();
    }

    /**
     * Menghapus catatan setelah berhasil masuk, supaya kegagalan sebelumnya
     * tidak terbawa ke percobaan berikutnya.
     */
    public function clear(string $email, ?string $ip): void
    {
        if (! $this->enabled()) {
            return;
        }

        foreach ($this->limits($email, $ip) as $limit) {
            try {
                $this->attempts->delete($limit['key']);
            } catch (Throwable) {
                // Catatan yang tertinggal akan terhapus sendiri saat jendelanya habis.
            }
        }
    }

    /**
     * Kalimat penolakan untuk ditampilkan di halaman masuk.
     */
    public function message(int $seconds): string
    {
        $menit = (int) ceil($seconds / 60);

        return $menit <= 1
            ? 'Terlalu banyak percobaan masuk yang gagal. Coba lagi kurang dari satu menit.'
            : 'Terlalu banyak percobaan masuk yang gagal. Coba lagi dalam ' . $menit . ' menit.';
    }

    // ---- Bagian dalam -----------------------------------------------------

    protected function enabled(): bool
    {
        return (bool) config('sigap.login_throttle.enabled', true);
    }

    /**
     * Daftar batas yang berlaku untuk satu percobaan.
     *
     * @return array<int, array{key: string, max: int, window: int, lock: int}>
     */
    protected function limits(string $email, ?string $ip): array
    {
        $email = mb_strtolower(trim($email));
        $alamat = trim((string) $ip) !== '' ? trim((string) $ip) : 'tanpa-alamat';

        return [
            $this->limit('akun', $email . '|' . $alamat, 'per_account', 5, 900, 900),
            $this->limit('surel', $email, 'per_email', 20, 900, 1800),
        ];
    }

    /**
     * @return array{key: string, max: int, window: int, lock: int}
     */
    protected function limit(
        string $awalan,
        string $penanda,
        string $bagian,
        int $maxBawaan,
        int $windowBawaan,
        int $lockBawaan
    ): array {
        $config = (array) config('sigap.login_throttle.' . $bagian, []);

        return [
            'key' => $awalan . '-' . $this->hash($penanda),
            'max' => max(1, (int) ($config['max'] ?? $maxBawaan)),
            'window' => max(60, (int) ($config['window'] ?? $windowBawaan)),
            'lock' => max(60, (int) ($config['lock'] ?? $lockBawaan)),
        ];
    }

    /**
     * Penanda diubah menjadi hash ber-kunci, supaya email tidak tersimpan
     * terbaca dan tidak dapat dicocokkan kembali oleh pihak lain yang
     * sekadar dapat membaca node ini.
     *
     * Hasilnya hanya memakai angka dan huruf, sehingga aman menjadi kunci
     * Firebase — kunci Firebase tidak boleh memuat titik, $, #, [, ] atau /.
     */
    protected function hash(string $penanda): string
    {
        return substr(hash_hmac('sha256', $penanda, (string) config('app.key')), 0, 20);
    }

    protected function lockedUntil(?Record $catatan): ?Carbon
    {
        if (! $catatan || blank($catatan->locked_until ?? null)) {
            return null;
        }

        try {
            return Carbon::parse((string) $catatan->locked_until);
        } catch (Throwable) {
            return null;
        }
    }

    protected function remainingLock(?Record $catatan): int
    {
        $sampai = $this->lockedUntil($catatan);

        if (! $sampai) {
            return 0;
        }

        $sisa = Carbon::now()->diffInSeconds($sampai, false);

        return $sisa > 0 ? (int) ceil($sisa) : 0;
    }

    protected function withinWindow(?Record $catatan, int $window, Carbon $sekarang): bool
    {
        if (! $catatan || blank($catatan->first_at ?? null)) {
            return false;
        }

        try {
            return Carbon::parse((string) $catatan->first_at)
                ->greaterThan($sekarang->copy()->subSeconds($window));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Membuang catatan yang jendelanya sudah lewat dan tidak lagi terkunci.
     *
     * Dijalankan sesekali saja — satu dari sekian percobaan gagal — karena
     * membaca seluruh node jauh lebih mahal daripada membaca satu kunci, dan
     * aplikasi ini tidak punya penjadwal tugas di Vercel.
     */
    protected function pruneSometimes(): void
    {
        $peluang = (int) config('sigap.login_throttle.prune_chance', 20);

        if ($peluang > 1 && random_int(1, $peluang) !== 1) {
            return;
        }

        $batas = max(
            (int) config('sigap.login_throttle.per_account.window', 900),
            (int) config('sigap.login_throttle.per_email.window', 900)
        );

        $kedaluwarsa = Carbon::now()->subSeconds($batas * 2);

        try {
            foreach ($this->attempts->all() as $catatan) {
                if ($this->remainingLock($catatan) > 0) {
                    continue;
                }

                if (blank($catatan->first_at ?? null)) {
                    continue;
                }

                if (Carbon::parse((string) $catatan->first_at)->lessThan($kedaluwarsa)) {
                    $this->attempts->delete((string) $catatan->id);
                }
            }
        } catch (Throwable) {
            // Pembersihan bersifat perapian, bukan syarat jalannya halaman masuk.
        }
    }
}
