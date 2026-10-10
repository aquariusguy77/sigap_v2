<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sambungan ke Firebase Realtime Database lewat REST API.
 *
 * Otorisasi ditentukan berurutan:
 *  1. Access token dari service account (dianjurkan, aman untuk produksi)
 *  2. Database secret lama lewat parameter ?auth= (masih didukung Firebase)
 *  3. Tanpa otorisasi, hanya berhasil bila aturan keamanan mengizinkan publik
 */
class FirebaseRealtimeDatabaseService
{
    public function __construct(
        protected FirebaseService $firebaseService,
        protected GoogleTokenService $tokens
    ) {
    }

    public function config(): array
    {
        return $this->firebaseService->config()['firebase'];
    }

    public function nodeMap(): array
    {
        return $this->config()['node_map'] ?? [];
    }

    public function path(string $node): string
    {
        return $this->config()['paths'][$node] ?? ('/' . trim($node, '/'));
    }

    public function enabled(): bool
    {
        return filled($this->config()['database_url'] ?? null)
            && (bool) config('sigap.data.firebase_read_enabled', true);
    }

    /**
     * Pesan galat terakhir, dipakai agar kegagalan tulis dapat dilaporkan
     * ke pengguna alih-alih berlalu diam-diam.
     */
    protected ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Hasil pembacaan node selama satu permintaan berlangsung.
     *
     * Satu halaman kerap membutuhkan node yang sama lebih dari sekali —
     * dasbor, misalnya, membaca /refugees tiga kali untuk menghitung jumlah,
     * menyusun ringkasan, dan menampilkan data terbaru. Tanpa ingatan ini,
     * ketiganya menjadi tiga perjalanan ke Firebase yang mengunduh isi yang
     * persis sama.
     *
     * Ingatan ini hanya hidup selama satu permintaan. Di Vercel setiap
     * permintaan dilayani proses yang berdiri sendiri, jadi tidak ada risiko
     * data basi terbawa ke permintaan berikutnya atau bocor ke pengguna lain.
     *
     * @var array<string, mixed>
     */
    protected array $ingatan = [];

    public function fetchNode(string $path): mixed
    {
        if (! $this->enabled()) {
            return null;
        }

        if (array_key_exists($path, $this->ingatan)) {
            return $this->ingatan[$path];
        }

        try {
            $response = $this->request()->get($this->endpoint($path));

            if ($response->failed()) {
                $this->lastError = 'Firebase menjawab kode ' . $response->status();

                return null;
            }

            return $this->ingatan[$path] = $response->json();
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return null;
        }
    }

    /**
     * Melupakan hasil pembacaan yang tersimpan.
     *
     * Dipanggil setiap kali ada penulisan. Yang dilupakan bukan hanya node
     * yang ditulis, melainkan juga seluruh induknya — menulis ke
     * /refugees/abc membuat isi /refugees yang tersimpan ikut usang — dan
     * seluruh turunannya.
     *
     * Tanpa ini, menyimpan data lalu membacanya kembali dalam permintaan yang
     * sama akan mengembalikan keadaan sebelum penyimpanan.
     */
    public function lupakan(?string $path = null): void
    {
        if ($path === null) {
            $this->ingatan = [];

            return;
        }

        $path = '/' . trim($path, '/');

        foreach (array_keys($this->ingatan) as $tersimpan) {
            /*
             * Kunci ingatan untuk pengambilan terbatas memuat embel-embel
             * "?limitToLast=...". Embel-embel itu dibuang lebih dulu supaya
             * hasil terbatas sebuah node ikut terlupakan saat node itu
             * berubah.
             */
            $bersih = explode('?', (string) $tersimpan, 2)[0];
            $t = '/' . trim($bersih, '/');

            if ($t === $path || str_starts_with($t, $path . '/') || str_starts_with($path, $t . '/')) {
                unset($this->ingatan[$tersimpan]);
            }
        }
    }

    /**
     * Mengambil sebagian terakhir isi sebuah node, diurutkan menurut kuncinya.
     *
     * Dipakai untuk daftar "terbaru". Tanpa ini, menampilkan tiga kegiatan
     * terakhir berarti mengunduh seluruh node riwayat lebih dulu — pada basis
     * data dengan 3.000 catatan, itu 1,1 MB yang diunduh hanya untuk dibuang
     * 2.997 di antaranya.
     *
     * Pengurutan sengaja memakai "$key", bukan kolom waktu. Firebase menolak
     * orderBy pada kolom yang belum didaftarkan di .indexOn dengan galat,
     * sedangkan kunci selalu terindeks dengan sendirinya — sehingga perubahan
     * ini tidak menuntut penyuntingan aturan keamanan sama sekali.
     *
     * Kunci yang dibuat Firebase lewat push berurut menurut waktu pembuatan,
     * jadi "terakhir menurut kunci" sama dengan "terbaru". Urutan akhirnya
     * tetap ditentukan ulang oleh repositori menurut kolom waktunya sendiri.
     */
    public function fetchLatest(string $path, int $limit): mixed
    {
        if (! $this->enabled() || $limit < 1) {
            return null;
        }

        $ingatanKunci = $path . '?limitToLast=' . $limit;

        if (array_key_exists($ingatanKunci, $this->ingatan)) {
            return $this->ingatan[$ingatanKunci];
        }

        try {
            $response = $this->request()
                ->withQueryParameters(['orderBy' => '"$key"', 'limitToLast' => $limit])
                ->get($this->endpoint($path));

            if ($response->failed()) {
                $this->lastError = 'Firebase menjawab kode ' . $response->status();

                return null;
            }

            return $this->ingatan[$ingatanKunci] = $response->json();
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return null;
        }
    }

    /**
     * Menghitung isi sebuah node tanpa mengunduh isinya.
     *
     * Firebase Realtime Database tidak menyediakan penghitung, tetapi
     * parameter shallow=true membuatnya mengembalikan kunci saja — tanpa
     * nilainya. Untuk node riwayat berisi 3.000 catatan, bedanya sekitar
     * 1,1 MB melawan 66 KB, padahal yang dibutuhkan hanya satu angka.
     */
    public function countNode(string $path): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $ingatanKunci = $path . '?shallow';

        if (array_key_exists($ingatanKunci, $this->ingatan)) {
            return (int) $this->ingatan[$ingatanKunci];
        }

        try {
            $response = $this->request()
                ->withQueryParameters(['shallow' => 'true'])
                ->get($this->endpoint($path));

            if ($response->failed()) {
                $this->lastError = 'Firebase menjawab kode ' . $response->status();

                return 0;
            }

            $isi = $response->json();

            return $this->ingatan[$ingatanKunci] = is_array($isi) ? count($isi) : 0;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return 0;
        }
    }

    public function fetchCollection(string $node, callable $normalizer): Collection
    {
        $snapshot = $this->fetchNode($this->path($node));

        if (! is_array($snapshot)) {
            return collect();
        }

        return collect($snapshot)
            ->map(fn ($payload, $key) => $normalizer((string) $key, is_array($payload) ? $payload : []))
            ->filter()
            ->values();
    }

    public function putNode(string $path, array $payload): bool
    {
        return $this->write('put', $path, $payload);
    }

    public function patchNode(string $path, array $payload): bool
    {
        return $this->write('patch', $path, $payload);
    }

    /**
     * Menambah data baru dan mengembalikan kunci yang dibuat Firebase.
     */
    public function pushNode(string $node, array $payload): ?string
    {
        if (! $this->enabled()) {
            $this->lastError = 'Sambungan Firebase tidak aktif.';

            return null;
        }

        try {
            $response = $this->request()->post($this->endpoint($this->path($node)), $payload);

            if (! $response->successful()) {
                $this->lastError = 'Firebase menjawab kode ' . $response->status();

                return null;
            }

            $this->lupakan($this->path($node));

            return $response->json('name');
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return null;
        }
    }

    public function deleteNode(string $path): bool
    {
        return $this->write('delete', $path);
    }

    public function sanitizeKey(string|int $value): string
    {
        $normalized = Str::of((string) $value)
            ->lower()
            ->replaceMatches('/[.#$\[\]\/]/', '-')
            ->replace(' ', '-')
            ->trim('-');

        return $normalized->isNotEmpty() ? (string) $normalized : (string) Str::uuid();
    }

    public function pushAuditTrail(array $payload): bool
    {
        return $this->pushNode('audit_trails', [
            'refugee_id' => $payload['refugee_id'] ?? null,
            'field_name' => $payload['field_name'] ?? null,
            'old_value' => $payload['old_value'] ?? null,
            'new_value' => $payload['new_value'] ?? null,
            'action_label' => $payload['action_label'] ?? 'Perubahan data',
            'performed_by_name' => $payload['performed_by_name'] ?? 'Sistem',
            'reason' => $payload['reason'] ?? null,
            'performed_at' => $payload['performed_at'] ?? now()->toIso8601String(),
        ]) !== null;
    }

    protected function endpoint(string $path): string
    {
        $base = rtrim((string) $this->config()['database_url'], '/');
        $trimmed = trim($path, '/');

        return $trimmed === '' ? $base . '/.json' : $base . '/' . $trimmed . '.json';
    }

    /**
     * Menyiapkan permintaan HTTP beserta otorisasinya.
     */
    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout(12)->acceptJson();

        $token = $this->tokens->accessToken(GoogleTokenService::SCOPE_DATABASE);

        if (filled($token)) {
            return $request->withToken($token);
        }

        $secret = $this->config()['database_secret'] ?? null;

        return filled($secret)
            ? $request->withQueryParameters(['auth' => $secret])
            : $request;
    }

    protected function write(string $method, string $path, ?array $payload = null): bool
    {
        if (! $this->enabled()) {
            $this->lastError = 'Sambungan Firebase tidak aktif.';

            return false;
        }

        try {
            $request = $this->request();

            $response = match ($method) {
                'put' => $request->put($this->endpoint($path), $payload ?? []),
                'patch' => $request->patch($this->endpoint($path), $payload ?? []),
                'delete' => $request->delete($this->endpoint($path)),
                default => null,
            };

            if ($response === null || ! $response->successful()) {
                $this->lastError = $response === null
                    ? 'Metode tulis tidak dikenali.'
                    : 'Firebase menjawab kode ' . $response->status() . ' ' . $response->body();

                return false;
            }

            $this->lupakan($path);

            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return false;
        }
    }
}
