<?php

namespace App\Services;

use App\Exceptions\DocumentUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Menyimpan berkas dokumen pendukung.
 *
 * Dua mode tersedia, dipilih lewat FIREBASE_STORAGE_DISK:
 *
 *  - "local"         : disimpan pada disk Laravel. Cocok untuk pengembangan di
 *                      komputer sendiri. TIDAK cocok untuk hosting serverless
 *                      karena berkas hilang setelah permintaan selesai.
 *  - "firebase-rest" : diunggah ke Firebase Storage lewat REST API Google Cloud
 *                      Storage. Inilah mode yang dipakai di lingkungan produksi.
 *
 * Catatan tentang izin akses:
 * Access token Google hanya berlaku satu jam, sehingga tidak bisa ditaruh
 * sebagai nilai tetap di environment. Service ini menukar service account
 * menjadi access token berumur pendek secara otomatis, lalu menyimpannya di
 * cache selama 55 menit.
 */
class FirebaseStorageService
{
    protected const CACHE_KEY = 'sigap.firebase.storage.token';
    protected const SCOPE = 'https://www.googleapis.com/auth/devstorage.read_write';
    protected const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    public function __construct(
        protected FirebaseService $firebaseService
    ) {
    }

    public function config(): array
    {
        return $this->firebaseService->config()['firebase'];
    }

    public function usesFirebase(): bool
    {
        return ($this->config()['storage_disk'] ?? 'local') === 'firebase-rest';
    }

    /**
     * Menyimpan berkas dan mengembalikan keterangan lokasinya.
     *
     * @throws DocumentUploadException bila berkas gagal disimpan.
     */
    public function storeDocument(UploadedFile $file, string $documentType): array
    {
        $config = $this->config();
        $disk = $config['storage_disk'] ?? 'local';
        $prefix = trim((string) ($config['storage_prefix'] ?? 'documents'), '/');

        $safeType = Str::of($documentType)->slug('-')->value() ?: 'dokumen';
        $extension = $file->getClientOriginalExtension();
        $fullName = now()->format('YmdHis')
            . '-' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . ($extension ? '.' . $extension : '');
        $path = $prefix . '/' . $safeType . '/' . $fullName;

        $result = [
            'disk' => $disk,
            'path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'firebase_document_key' => $this->documentKey($safeType, $fullName),
        ];

        if ($disk === 'firebase-rest') {
            return array_merge($result, $this->storeOnFirebase($file, $path));
        }

        try {
            $stored = $file->storeAs($prefix . '/' . $safeType, $fullName, $disk);
        } catch (Throwable $e) {
            throw DocumentUploadException::penyimpananLokalGagal();
        }

        if ($stored === false) {
            throw DocumentUploadException::penyimpananLokalGagal();
        }

        return array_merge($result, ['download_url' => null]);
    }

    /**
     * Mengunggah berkas ke Firebase Storage.
     *
     * Berkas dikirim memakai unggahan multipart agar metadata
     * firebaseStorageDownloadTokens ikut tertanam. Metadata inilah yang membuat
     * berkas punya tautan unduh yang bisa dibuka langsung dari peramban.
     *
     * @throws DocumentUploadException
     */
    protected function storeOnFirebase(UploadedFile $file, string $path): array
    {
        $bucket = trim((string) ($this->config()['storage_bucket'] ?? ''), '/');

        if ($bucket === '') {
            throw DocumentUploadException::bucketBelumDisetel();
        }

        $token = $this->accessToken();
        $downloadToken = (string) Str::uuid();
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $binary = @file_get_contents($file->getRealPath());

        if ($binary === false) {
            throw DocumentUploadException::unggahGagal('Berkas tidak terbaca dari penyimpanan sementara.');
        }

        $boundary = 'sigap' . Str::random(24);
        $metadata = json_encode([
            'name' => $path,
            'contentType' => $mime,
            'metadata' => ['firebaseStorageDownloadTokens' => $downloadToken],
        ], JSON_UNESCAPED_SLASHES);

        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . $metadata . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: {$mime}\r\n\r\n"
            . $binary . "\r\n"
            . "--{$boundary}--";

        try {
            $response = Http::timeout(30)
                ->withToken($token)
                ->withBody($body, "multipart/related; boundary={$boundary}")
                ->post("https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o?uploadType=multipart");
        } catch (Throwable $e) {
            throw DocumentUploadException::unggahGagal($e->getMessage());
        }

        if (! $response->successful()) {
            throw DocumentUploadException::unggahGagal(
                'Firebase menjawab kode ' . $response->status() . '. '
                . (string) $response->json('error.message', '')
            );
        }

        return [
            'download_url' => $this->downloadUrl($bucket, $path, $downloadToken),
            'download_token' => $downloadToken,
        ];
    }

    /**
     * Tautan unduh publik Firebase Storage untuk sebuah berkas.
     */
    public function downloadUrl(string $bucket, string $path, string $downloadToken): string
    {
        return 'https://firebasestorage.googleapis.com/v0/b/' . $bucket
            . '/o/' . rawurlencode($path)
            . '?alt=media&token=' . $downloadToken;
    }

    /**
     * Memperoleh access token untuk Google Cloud Storage.
     *
     * @throws DocumentUploadException
     */
    protected function accessToken(): string
    {
        // Token tetap hanya disediakan untuk uji coba singkat, karena masa
        // berlakunya habis dalam satu jam.
        $static = trim((string) ($this->config()['storage_bearer_token'] ?? ''));

        if ($static !== '') {
            return $static;
        }

        $credentials = $this->serviceAccount();

        if ($credentials === null) {
            throw DocumentUploadException::kredensialBelumDisetel();
        }

        $token = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(55),
            fn () => $this->requestAccessToken($credentials)
        );

        if (blank($token)) {
            Cache::forget(self::CACHE_KEY);

            throw DocumentUploadException::tokenGagal();
        }

        return $token;
    }

    /**
     * Menukar service account menjadi access token lewat alur JWT bearer.
     */
    protected function requestAccessToken(array $credentials): ?string
    {
        $now = time();

        $jwt = $this->signJwt([
            'iss' => $credentials['client_email'] ?? '',
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_ENDPOINT,
            'iat' => $now,
            'exp' => $now + 3600,
        ], (string) ($credentials['private_key'] ?? ''));

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

    protected function signJwt(array $claims, string $privateKey): ?string
    {
        if ($privateKey === '') {
            return null;
        }

        $input = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
            . '.' . $this->base64Url(json_encode($claims));

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
     * untuk dasbor yang tidak nyaman menerima teks berbaris banyak), atau jalur
     * menuju berkas JSON pada server.
     */
    protected function serviceAccount(): ?array
    {
        $raw = trim((string) ($this->config()['service_account'] ?? ''));

        if ($raw === '') {
            return null;
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

        return is_array($parsed) && filled($parsed['private_key'] ?? null) ? $parsed : null;
    }

    public function documentKey(string $documentType, string $fileName): string
    {
        return Str::of($documentType . '-' . $fileName)
            ->lower()
            ->replaceMatches('/[^a-z0-9.\-_]+/', '-')
            ->trim('-')
            ->value() ?: ('document-' . Str::uuid());
    }

    /**
     * Tautan untuk membuka berkas yang sudah tersimpan sebelumnya.
     */
    public function previewUrl(?string $path, ?string $downloadToken = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        $config = $this->config();
        $bucket = trim((string) ($config['storage_bucket'] ?? ''), '/');
        $baseUrl = rtrim((string) ($config['storage_public_base_url'] ?? ''), '/');

        if (filled($downloadToken) && $bucket !== '') {
            return $this->downloadUrl($bucket, $path, $downloadToken);
        }

        if ($baseUrl !== '') {
            return $baseUrl . '/' . ltrim($path, '/');
        }

        if ($this->usesFirebase() && $bucket !== '') {
            return 'https://firebasestorage.googleapis.com/v0/b/' . $bucket
                . '/o/' . rawurlencode($path) . '?alt=media';
        }

        if (! $this->usesFirebase() && Storage::disk($config['storage_disk'] ?? 'local')->exists($path)) {
            return null;
        }

        return null;
    }
}
