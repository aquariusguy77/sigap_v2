<?php

namespace App\Firebase;

class PlacementRepository extends Repository
{
    protected string $node = 'placements';

    protected array $fields = [
        'refugee_id',
        'refugee_name',
        'location_name',
        'entered_at',
        'exited_at',
        'placement_status',
        'notes',
    ];

    protected array $dates = ['entered_at', 'exited_at'];

    protected string $sortBy = 'entered_at';

    protected function decorate(array $payload): array
    {
        $payload['location_name'] = $payload['location_name'] ?? '-';
        $payload['placement_status'] = $payload['placement_status'] ?? 'Aktif';
        $payload['title'] = $payload['refugee_name'] ?? $payload['location_name'];
        $payload['detail'] = trim(($payload['location_name'] ?? '-') . ' • ' . $payload['placement_status']);
        $payload['note'] = $payload['notes'] ?? 'Belum ada catatan penempatan.';

        return $payload;
    }
}
