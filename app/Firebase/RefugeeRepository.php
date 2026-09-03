<?php

namespace App\Firebase;

class RefugeeRepository extends Repository
{
    protected string $node = 'refugees';

    protected array $fields = [
        'internal_id',
        'name',
        'nationality',
        'unhcr_number',
        'status',
        'location',
        'document_status',
        'notes',
        'registered_at',
    ];

    protected array $dates = ['registered_at'];

    protected string $sortBy = 'name';

    protected bool $sortDescending = false;

    protected function decorate(array $payload): array
    {
        $payload['name'] = $payload['name'] ?? 'Tanpa nama';
        $payload['internal_id'] = $payload['internal_id'] ?? strtoupper((string) $payload['id']);
        $payload['nationality'] = $payload['nationality'] ?? '-';
        $payload['status'] = $payload['status'] ?? 'Perlu Verifikasi';
        $payload['location'] = $payload['location'] ?? '-';
        $payload['document_status'] = $payload['document_status'] ?? 'Belum Lengkap';
        $payload['updated_at_label'] = $this->label($payload['updated_at'] ?? null);

        return $payload;
    }

    protected function label(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->translatedFormat('d M Y, H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
