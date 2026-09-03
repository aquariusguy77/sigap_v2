<?php

namespace App\Services;

use App\Firebase\AuditTrailRepository;
use App\Firebase\DocumentRepository;
use App\Firebase\PlacementRepository;
use App\Firebase\Record;
use App\Firebase\RefugeeRepository;
use App\Firebase\ReportLogRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Menyiapkan data untuk ditampilkan di halaman.
 *
 * Seluruh isinya berasal dari Firebase Realtime Database melalui repository,
 * sehingga aplikasi tidak memerlukan database relasional sama sekali.
 */
class SigapDataService
{
    public function __construct(
        protected RefugeeRepository $refugees,
        protected PlacementRepository $placements,
        protected DocumentRepository $documents,
        protected AuditTrailRepository $audits,
        protected ReportLogRepository $reportLogs
    ) {
    }

    // ------------------------------------------------------------------
    // Ringkasan
    // ------------------------------------------------------------------

    public function stats(): array
    {
        $refugees = $this->refugees();
        $documents = $this->documents();
        $activities = $this->history();

        $active = $refugees->filter(fn (Record $item) => $item->status === 'Aktif')->count();
        $complete = $refugees->filter(fn (Record $item) => $item->document_status === 'Lengkap')->count();
        $needsReview = $documents->filter(
            fn (Record $item) => in_array($item->verification_status, ['Perlu Verifikasi', 'Belum Lengkap'], true)
        )->count();

        return [
            [
                'label' => 'Total Pengungsi',
                'value' => $refugees->count(),
                'note' => $active . ' di antaranya berstatus aktif di hunian.',
                'icon' => 'users',
                'tone' => 'blue',
            ],
            [
                'label' => 'Dokumen Lengkap',
                'value' => $complete,
                'note' => 'Berkas identitas dan administrasi sudah tervalidasi.',
                'icon' => 'file',
                'tone' => 'green',
            ],
            [
                'label' => 'Perlu Verifikasi',
                'value' => $needsReview,
                'note' => 'Dokumen menunggu pemeriksaan supervisor.',
                'icon' => 'alert',
                'tone' => 'orange',
            ],
            [
                'label' => 'Catatan Aktivitas',
                'value' => $activities->count(),
                'note' => 'Perubahan data yang tercatat di log aktivitas.',
                'icon' => 'history',
                'tone' => 'deep',
            ],
        ];
    }

    public function locationSummary(int $limit = 5): Collection
    {
        $refugees = $this->refugees();
        $total = max(1, $refugees->count());

        return $refugees
            ->groupBy(fn (Record $item) => $item->location ?: 'Tidak diketahui')
            ->map(fn (Collection $group, string $location) => [
                'location' => $location,
                'count' => $group->count(),
                'percent' => (int) round(($group->count() / $total) * 100),
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values();
    }

    // ------------------------------------------------------------------
    // Pengungsi
    // ------------------------------------------------------------------

    public function refugees(): Collection
    {
        return $this->refugees->all();
    }

    public function refugeeById(string $id): ?Record
    {
        return $this->refugees->find($id);
    }

    public function filteredRefugees(array $filters): Collection
    {
        $keyword = mb_strtolower(trim((string) ($filters['keyword'] ?? '')));

        return $this->refugees()->filter(function (Record $refugee) use ($filters, $keyword) {
            $matchesKeyword = $keyword === ''
                || str_contains(mb_strtolower((string) $refugee->name), $keyword)
                || str_contains(mb_strtolower((string) $refugee->internal_id), $keyword);

            return $matchesKeyword
                && $this->matches($filters['nationality'] ?? '', $refugee->nationality)
                && $this->matches($filters['status'] ?? '', $refugee->status)
                && $this->matches($filters['location'] ?? '', $refugee->location)
                && $this->matches($filters['document_status'] ?? '', $refugee->document_status);
        })->values();
    }

    public function paginatedFilteredRefugees(array $filters): LengthAwarePaginator
    {
        $sortMap = [
            'name' => 'name',
            'internal_id' => 'internal_id',
            'nationality' => 'nationality',
            'status' => 'status',
            'location' => 'location',
            'updated_at' => 'updated_at',
        ];

        $key = $sortMap[$filters['sort'] ?? 'name'] ?? 'name';
        $items = $this->filteredRefugees($filters)
            ->sortBy(fn (Record $item) => mb_strtolower((string) $item->{$key}))
            ->values();

        if (($filters['direction'] ?? 'asc') === 'desc') {
            $items = $items->reverse()->values();
        }

        return $this->paginate($items, (int) ($filters['per_page'] ?? 10));
    }

    public function refugeeFilterOptions(): array
    {
        $refugees = $this->refugees();

        return [
            'nationalities' => $refugees->pluck('nationality')->filter()->unique()->sort()->values(),
            'statuses' => $refugees->pluck('status')->filter()->unique()->sort()->values(),
            'locations' => $refugees->pluck('location')->filter()->unique()->sort()->values(),
            'documentStatuses' => collect(['Lengkap', 'Perlu Verifikasi', 'Belum Lengkap']),
        ];
    }

    public function refugeeSelectOptions(): Collection
    {
        return $this->refugees()->map(fn (Record $refugee) => [
            'id' => $refugee->id,
            'name' => $refugee->name,
            'internal_id' => $refugee->internal_id,
        ])->values();
    }

    // ------------------------------------------------------------------
    // Penempatan
    // ------------------------------------------------------------------

    public function placements(): Collection
    {
        return $this->placements->all();
    }

    public function placementById(string $id): ?Record
    {
        return $this->placements->find($id);
    }

    public function placementForRefugee(string $refugeeId): ?Record
    {
        return $this->placements()->first(fn (Record $item) => (string) $item->refugee_id === $refugeeId);
    }

    public function filteredPlacements(array $filters = []): Collection
    {
        $keyword = mb_strtolower(trim((string) ($filters['keyword'] ?? '')));

        return $this->placements()->filter(function (Record $placement) use ($filters, $keyword) {
            $matchesKeyword = $keyword === ''
                || str_contains(mb_strtolower((string) $placement->title), $keyword)
                || str_contains(mb_strtolower((string) $placement->location_name), $keyword);

            return $matchesKeyword && $this->matches($filters['status'] ?? '', $placement->placement_status);
        })->values();
    }

    public function paginatedFilteredPlacements(array $filters = []): LengthAwarePaginator
    {
        return $this->paginate($this->filteredPlacements($filters), (int) ($filters['per_page'] ?? 10));
    }

    public function placementStatusOptions(): Collection
    {
        return collect(['Aktif', 'Mutasi', 'Selesai', 'Transit']);
    }

    // ------------------------------------------------------------------
    // Dokumen
    // ------------------------------------------------------------------

    public function documents(): Collection
    {
        return $this->documents->all();
    }

    public function documentById(string $id): ?Record
    {
        return $this->documents->find($id);
    }

    public function documentForRefugee(string $refugeeId): ?Record
    {
        return $this->documents()->first(fn (Record $item) => (string) $item->refugee_id === $refugeeId);
    }

    public function filteredDocuments(array $filters = []): Collection
    {
        $keyword = mb_strtolower(trim((string) ($filters['keyword'] ?? '')));

        return $this->documents()->filter(function (Record $document) use ($filters, $keyword) {
            $matchesKeyword = $keyword === ''
                || str_contains(mb_strtolower((string) $document->document_type), $keyword)
                || str_contains(mb_strtolower((string) $document->meta), $keyword)
                || str_contains(mb_strtolower((string) $document->file_name), $keyword);

            return $matchesKeyword
                && $this->matches($filters['status'] ?? '', $document->verification_status)
                && $this->matches($filters['type'] ?? '', $document->document_type);
        })->values();
    }

    public function paginatedFilteredDocuments(array $filters = []): LengthAwarePaginator
    {
        return $this->paginate($this->filteredDocuments($filters), (int) ($filters['per_page'] ?? 10));
    }

    public function documentStatusOptions(): Collection
    {
        return collect(['Lengkap', 'Perlu Verifikasi', 'Belum Lengkap']);
    }

    /**
     * Berkas yang wajib dikumpulkan pengungsi.
     *
     * Hanya dua jenis: Kartu Pengungsi dan Kartu Wajib Lapor.
     */
    public function documentTypeOptions(): Collection
    {
        return collect(config('sigap.reference.document_types', []))
            ->filter()
            ->values();
    }

    /**
     * Kategori penempatan beserta keterangannya, dikunci "iom" dan "mandiri".
     */
    public function placementCategoryOptions(): Collection
    {
        return collect(config('sigap.reference.placement_categories', []));
    }

    /**
     * Daftar Community House tempat pengungsi berfasilitas IOM ditempatkan.
     */
    public function communityHouseOptions(): Collection
    {
        return collect(config('sigap.reference.community_houses', []))
            ->filter()
            ->values();
    }

    // ------------------------------------------------------------------
    // Riwayat & laporan
    // ------------------------------------------------------------------

    public function history(): Collection
    {
        return $this->audits->all();
    }

    public function historyForRefugee(string $refugeeId): Collection
    {
        return $this->history()
            ->filter(fn (Record $item) => (string) $item->refugee_id === $refugeeId)
            ->values();
    }

    public function recentActivities(int $limit = 6): Collection
    {
        return $this->history()->take($limit);
    }

    public function reportLogs(): Collection
    {
        return $this->reportLogs->all()->take(20)->map(fn (Record $log) => [
            'type' => $log->type,
            'filters' => $log->filters ?? '-',
            'format' => $log->format ?? 'CSV',
            'actor' => $log->actor,
            'downloaded_at' => $log->downloaded_at_label,
        ])->values();
    }

    // ------------------------------------------------------------------
    // Pembantu
    // ------------------------------------------------------------------

    protected function matches(string $filter, mixed $value): bool
    {
        return $filter === '' || (string) $value === $filter;
    }

    protected function paginate(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = max(5, min($perPage, 20));

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
