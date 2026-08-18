<?php

namespace App\Firebase;

use Illuminate\Support\Carbon;
use Throwable;

class ReportLogRepository extends Repository
{
    protected string $node = 'reports';

    protected array $fields = [
        'name',
        'format',
        'filters',
        'downloaded_by',
        'downloaded_at',
    ];

    protected string $sortBy = 'downloaded_at';

    /**
     * Mencatat satu unduhan laporan tanpa mengganggu proses unduh itu sendiri.
     */
    public function record(array $payload): void
    {
        try {
            $this->create(array_merge(['downloaded_at' => now()->toIso8601String()], $payload));
        } catch (Throwable) {
            // Riwayat gagal dicatat; berkas tetap dikirim ke petugas.
        }
    }

    protected function decorate(array $payload): array
    {
        $payload['type'] = $payload['name'] ?? 'Laporan';
        $payload['actor'] = $payload['downloaded_by'] ?? 'Sistem';
        $payload['downloaded_at_label'] = $this->label($payload['downloaded_at'] ?? null);

        return $payload;
    }

    protected function label(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->translatedFormat('d M Y • H:i');
        } catch (Throwable) {
            return (string) $value;
        }
    }
}
