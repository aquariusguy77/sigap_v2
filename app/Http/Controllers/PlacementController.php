<?php

namespace App\Http\Controllers;

use App\Exceptions\FirebaseWriteException;
use App\Firebase\AuditTrailRepository;
use App\Firebase\PlacementRepository;
use App\Firebase\Record;
use App\Firebase\RefugeeRepository;
use App\Http\Requests\PlacementUpsertRequest;
use App\Services\SigapDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlacementController extends Controller
{
    public function __construct(
        protected SigapDataService $sigapDataService,
        protected PlacementRepository $placements,
        protected RefugeeRepository $refugees,
        protected AuditTrailRepository $audits
    ) {
    }

    public function index(): View
    {
        $filters = [
            'keyword' => trim((string) request('keyword', '')),
            'status' => (string) request('status', ''),
            'per_page' => max(5, min((int) request('per_page', 10), 20)),
        ];

        return view('placements.index', array_merge($this->baseViewData(), [
            'pageHeading' => 'Penempatan',
            'pageDescription' => 'Lokasi hunian aktif dan riwayat mutasi pengungsi.',
            'placements' => $this->sigapDataService->paginatedFilteredPlacements($filters),
            'activeFilters' => $filters,
            'statusOptions' => $this->sigapDataService->placementStatusOptions(),
            'categoryOptions' => $this->sigapDataService->placementCategoryOptions(),
        ]));
    }

    public function create(): View
    {
        $this->ensureAbility('manage-placements');

        return view('placements.create', array_merge($this->baseViewData(), [
            'pageHeading' => 'Tambah Penempatan',
            'pageDescription' => 'Isi lokasi hunian, tanggal masuk, dan status penempatan.',
            'placement' => new Record(),
            'formAction' => route('placements.store'),
            'formMethod' => 'POST',
            'refugees' => $this->sigapDataService->refugeeSelectOptions(),
            'statusOptions' => $this->sigapDataService->placementStatusOptions(),
            'categoryOptions' => $this->sigapDataService->placementCategoryOptions(),
            'communityHouses' => $this->sigapDataService->communityHouseOptions(),
        ]));
    }

    public function show(string $placement): View
    {
        $record = $this->findOrFail($placement);

        return view('placements.show', array_merge($this->baseViewData(), [
            'pageHeading' => 'Detail Penempatan',
            'pageDescription' => 'Rincian lokasi, tanggal, dan status penempatan.',
            'placement' => $record,
            'placementView' => $record,
            'refugeeView' => $this->sigapDataService->refugeeById((string) $record->refugee_id),
        ]));
    }

    public function store(PlacementUpsertRequest $request): RedirectResponse
    {
        $this->ensureAbility('manage-placements');
        $payload = $this->withRefugeeName($request->payload());

        try {
            $placement = $this->placements->create($payload);
        } catch (FirebaseWriteException $e) {
            return back()->withInput()->withErrors(['category' => $e->getMessage()]);
        }

        $this->audits->record([
            'refugee_id' => $placement->refugee_id,
            'field_name' => 'Penempatan',
            'new_value' => $placement->location_name,
            'action_label' => 'Penempatan ditambahkan',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Pencatatan lokasi hunian',
        ]);

        return redirect()
            ->route('placements.index')
            ->with('status', 'Data penempatan berhasil ditambahkan.');
    }

    public function edit(string $placement): View
    {
        $this->ensureAbility('manage-placements');

        return view('placements.edit', array_merge($this->baseViewData(), [
            'pageHeading' => 'Ubah Penempatan',
            'pageDescription' => 'Perbarui lokasi, tanggal, dan status penempatan.',
            'placement' => $this->findOrFail($placement),
            'formAction' => route('placements.update', $placement),
            'formMethod' => 'PUT',
            'refugees' => $this->sigapDataService->refugeeSelectOptions(),
            'statusOptions' => $this->sigapDataService->placementStatusOptions(),
            'categoryOptions' => $this->sigapDataService->placementCategoryOptions(),
            'communityHouses' => $this->sigapDataService->communityHouseOptions(),
        ]));
    }

    public function update(PlacementUpsertRequest $request, string $placement): RedirectResponse
    {
        $this->ensureAbility('manage-placements');
        $existing = $this->findOrFail($placement);
        $payload = $this->withRefugeeName($request->payload());

        try {
            $updated = $this->placements->update($placement, $payload);
        } catch (FirebaseWriteException $e) {
            return back()->withInput()->withErrors(['category' => $e->getMessage()]);
        }

        $this->audits->record([
            'refugee_id' => $updated->refugee_id,
            'field_name' => 'Penempatan',
            'old_value' => $existing->location_name,
            'new_value' => $updated->location_name,
            'action_label' => 'Penempatan diperbarui',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Perubahan lokasi hunian',
        ]);

        return redirect()
            ->route('placements.index')
            ->with('status', 'Data penempatan berhasil diperbarui.');
    }

    public function destroy(string $placement): RedirectResponse
    {
        $this->ensureAbility('full-access');
        $existing = $this->findOrFail($placement);

        try {
            $this->placements->delete($placement);
        } catch (FirebaseWriteException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        $this->audits->record([
            'refugee_id' => $existing->refugee_id,
            'field_name' => 'Penempatan',
            'old_value' => $existing->location_name,
            'action_label' => 'Penempatan dihapus',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Penghapusan data penempatan',
        ]);

        return redirect()
            ->route('placements.index')
            ->with('status', 'Data penempatan berhasil dihapus.');
    }

    /**
     * Menyimpan nama pengungsi bersama penempatan supaya daftar tidak perlu
     * membaca ulang node lain hanya untuk menampilkan nama.
     */
    protected function withRefugeeName(array $payload): array
    {
        $refugee = filled($payload['refugee_id'] ?? null)
            ? $this->refugees->find((string) $payload['refugee_id'])
            : null;

        $payload['refugee_name'] = $refugee?->name;

        return $payload;
    }

    protected function findOrFail(string $id): Record
    {
        $record = $this->placements->find($id);

        abort_if($record === null, 404, 'Data penempatan tidak ditemukan.');

        return $record;
    }
}
