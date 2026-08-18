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

    public function fetchNode(string $path): mixed
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = $this->request()->get($this->endpoint($path));

            if ($response->failed()) {
                $this->lastError = 'Firebase menjawab kode ' . $response->status();

                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return null;
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

            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return false;
        }
    }
}
