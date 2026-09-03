<?php

namespace App\Firebase;

use Illuminate\Support\Str;

class PlacementRepository extends Repository
{
    protected string $node = 'placements';

    protected array $fields = [
        'refugee_id',
        'refugee_name',
        // "iom" atau "mandiri".
        'category',
        // Diisi hanya untuk kategori iom.
        'community_house',
        // Diisi hanya untuk kategori mandiri.
        'address',
        'latitude',
        'longitude',
        // Ringkasan lokasi, diturunkan dari dua kolom di atas.
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
        $payload['category'] = $this->normalizeCategory($payload['category'] ?? null);
        $payload['category_label'] = $payload['category'] === 'mandiri' ? 'Mandiri' : 'Fasilitas IOM';
        $payload['is_mandiri'] = $payload['category'] === 'mandiri';

        $payload['location_name'] = $this->resolveLocationName($payload);
        $payload['placement_status'] = $payload['placement_status'] ?? 'Aktif';
        $payload['title'] = $payload['refugee_name'] ?? $payload['location_name'];
        $payload['detail'] = trim(($payload['location_name'] ?? '-') . ' • ' . $payload['placement_status']);
        $payload['note'] = $payload['notes'] ?? 'Belum ada catatan penempatan.';

        $destination = $this->mapDestination($payload);
        $payload['map_url'] = $this->mapUrl($destination);
        $payload['directions_url'] = $this->directionsUrl($destination);
        $payload['has_map'] = filled($destination);

        return $payload;
    }

    protected function normalizeCategory(mixed $value): string
    {
        return Str::lower(trim((string) $value)) === 'mandiri' ? 'mandiri' : 'iom';
    }

    /**
     * Ringkasan lokasi yang dipakai di daftar, dasbor, dan laporan.
     *
     * Pengungsi berfasilitas IOM cukup diwakili nama Community House-nya.
     * Pengungsi mandiri diwakili alamatnya, karena setiap orang berbeda.
     */
    protected function resolveLocationName(array $payload): string
    {
        if (($payload['category'] ?? 'iom') === 'mandiri') {
            $address = trim((string) ($payload['address'] ?? ''));

            if ($address !== '') {
                return Str::limit($address, 90);
            }
        } else {
            $house = trim((string) ($payload['community_house'] ?? ''));

            if ($house !== '') {
                return $house;
            }
        }

        $existing = trim((string) ($payload['location_name'] ?? ''));

        return $existing !== '' ? $existing : '-';
    }

    /**
     * Titik tujuan untuk Google Maps.
     *
     * Koordinat lebih diutamakan karena menunjuk satu titik pasti. Alamat
     * dipakai bila koordinat belum diisi; Google Maps tetap dapat mencarinya,
     * hanya ketelitiannya bergantung pada kelengkapan alamat.
     */
    protected function mapDestination(array $payload): string
    {
        if (($payload['category'] ?? 'iom') !== 'mandiri') {
            return '';
        }

        $lat = trim((string) ($payload['latitude'] ?? ''));
        $lng = trim((string) ($payload['longitude'] ?? ''));

        if (is_numeric($lat) && is_numeric($lng)) {
            return $lat . ',' . $lng;
        }

        return trim((string) ($payload['address'] ?? ''));
    }

    protected function mapUrl(string $destination): ?string
    {
        return $destination === ''
            ? null
            : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($destination);
    }

    protected function directionsUrl(string $destination): ?string
    {
        return $destination === ''
            ? null
            : 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($destination);
    }
}
