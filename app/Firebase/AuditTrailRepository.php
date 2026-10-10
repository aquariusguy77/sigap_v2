<?php

namespace App\Firebase;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
     * Sejumlah catatan terakhir saja.
     *
     * Dibedakan dari all()->take($n) karena yang membatasi di sini adalah
     * Firebase, bukan PHP. Pada node dengan 3.000 catatan, all()->take(3)
     * tetap mengunduh ketiga ribunya lebih dulu — sekitar 1,1 MB — hanya
     * untuk membuang 2.997 di antaranya.
     *
     * Diambil sedikit lebih banyak daripada yang diminta, lalu diurutkan
     * ulang menurut performed_at. Firebase mengurutkan menurut kunci,
     * sedangkan urutan yang ditampilkan mengikuti waktu kejadian; keduanya
     * hampir selalu sama karena kunci push berurut menurut waktu pembuatan,
     * tetapi kelebihan itu menjaga urutannya tetap benar bila ada catatan
     * yang waktunya diisi mundur.
     */
    public function recent(int $limit): Collection
    {
        $limit = max(1, $limit);

        $snapshot = $this->firebase->fetchLatest(
            $this->firebase->path($this->node),
            min(100, $limit * 4)
        );

        if (! is_array($snapshot)) {
            return collect();
        }

        return collect($snapshot)
            ->map(fn ($payload, $key) => is_array($payload) ? $this->hydrate((string) $key, $payload) : null)
            ->filter()
            ->sortByDesc(fn (Record $record) => (string) ($record->attributes['performed_at'] ?? ''))
            ->values()
            ->take($limit);
    }

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
