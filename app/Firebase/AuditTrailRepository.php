<?php

namespace App\Firebase;

use Illuminate\Support\Carbon;
use Throwable;

class AuditTrailRepository extends Repository
{
    protected string $node = 'audit_trails';

    protected array $fields = [
        'refugee_id',
        'field_name',
        'old_value',
        'new_value',
        'action_label',
        'performed_by_name',
        'reason',
        'performed_at',
    ];

    protected string $sortBy = 'performed_at';

    /**
     * Mencatat satu perubahan data.
     *
     * Pencatatan riwayat tidak boleh menggagalkan tindakan utama, sehingga
     * kegagalan di sini sengaja dibiarkan tanpa melempar galat.
     */
    public function record(array $payload): void
    {
        try {
            $this->create(array_merge([
                'action_label' => 'Perubahan data',
                'performed_by_name' => 'Sistem',
                'performed_at' => now()->toIso8601String(),
            ], $payload));
        } catch (Throwable) {
            // Riwayat gagal dicatat; tindakan utama tetap dianggap berhasil.
        }
    }

    protected function decorate(array $payload): array
    {
        $payload['title'] = $payload['action_label'] ?? 'Perubahan data';
        $payload['actor'] = $payload['performed_by_name'] ?? 'Sistem';
        $payload['detail'] = trim(
            ($payload['field_name'] ?? 'Perubahan data')
            . ' dari ' . ($payload['old_value'] ?? '-')
            . ' ke ' . ($payload['new_value'] ?? '-')
        );
        $payload['time'] = $this->label($payload['performed_at'] ?? null);

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
