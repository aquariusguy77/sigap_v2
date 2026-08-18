<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Menukar service account Firebase menjadi access token Google.
 *
 * Access token Google hanya berlaku satu jam, sehingga tidak bisa disimpan
 * sebagai nilai tetap di environment. Layanan ini menandatangani JWT dengan
 * kunci privat service account, menukarnya menjadi access token, lalu
 * menyimpannya di cache selama 55 menit.
 *
 * Dipakai bersama oleh dua bagian:
 *  - Realtime Database, untuk membaca dan menulis data
 *  - Cloud Storage, untuk mengunggah berkas dokumen
 */
class GoogleTokenService
{
    public const SCOPE_DATABASE = 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email';
    public const SCOPE_STORAGE = 'https://www.googleapis.com/auth/devstorage.read_write';

    protected const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    /** Menghindari pembacaan dan penguraian JSON berulang dalam satu permintaan. */
    protected ?array $cachedAccount = null;

    protected bool $accountResolved = false;

    /**
     * Access token untuk scope tertentu, atau null bila kredensial belum ada.
     */
    public function accessToken(string $scope): ?string
    {
        $account = $this->serviceAccount();

        if ($account === null) {
            return null;
        }

        $key = 'sigap.google.token.' . md5($scope . ($account['client_email'] ?? ''));

        $token = Cache::remember($key, now()->addMinutes(55), fn () => $this->requestToken($account, $scope));

        if (blank($token)) {
            Cache::forget($key);

            return null;
        }

        return $token;
    }

    public function hasServiceAccount(): bool
    {
        return $this->serviceAccount() !== null;
    }

    public function projectId(): ?string
    {
        return $this->serviceAccount()['project_id'] ?? null;
    }

    protected function requestToken(array $account, string $scope): ?string
    {
        $now = time();

        $jwt = $this->signJwt([
            'iss' => $account['client_email'] ?? '',
            'scope' => $scope,
            'aud' => self::TOKEN_ENDPOINT,
            'iat' => $now,
            'exp' => $now + 3600,
        ], (string) ($account['private_key'] ?? ''));

        if ($jwt === null) {
            return null;
        }

        try {
            $response = Http::asForm()->timeout(15)->post(self::TOKEN_ENDPOINT, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->json('access_token') : null;
    }

    public function signJwt(array $claims, string $privateKey): ?string
    {
        if (trim($privateKey) === '') {
            return null;
        }

        $input = $this->base64Url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
            . '.' . $this->base64Url((string) json_encode($claims));

        $signature = '';

        try {
            $signed = openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        } catch (Throwable) {
            return null;
        }

        return $signed ? $input . '.' . $this->base64Url($signature) : null;
    }

    protected function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Membaca service account dari environment.
     *
     * Nilainya boleh berupa JSON apa adanya, JSON yang sudah di-base64 (berguna
     * untuk dasbor hosting yang sulit menerima teks berbaris banyak), atau jalur
     * menuju berkas JSON pada server.
     */
    public function serviceAccount(): ?array
    {
        if ($this->accountResolved) {
            return $this->cachedAccount;
        }

        $this->accountResolved = true;
        $raw = trim((string) config('sigap.firebase.service_account', ''));

        if ($raw === '') {
            return $this->cachedAccount = null;
        }

        if (! str_starts_with($raw, '{')) {
            $decoded = base64_decode($raw, true);

            if ($decoded !== false && str_starts_with(trim($decoded), '{')) {
                $raw = $decoded;
            } elseif (is_file($raw) && is_readable($raw)) {
                $raw = (string) file_get_contents($raw);
            }
        }

        $parsed = json_decode($raw, true);

        return $this->cachedAccount = is_array($parsed) && filled($parsed['private_key'] ?? null)
            ? $parsed
            : null;
    }
}
