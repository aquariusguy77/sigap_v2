<?php

namespace App\Http\Controllers;

use App\Exceptions\FirebaseWriteException;
use App\Firebase\AuditTrailRepository;
use App\Firebase\DocumentRepository;
use App\Firebase\Record;
use App\Firebase\RefugeeRepository;
use App\Http\Requests\RefugeeFilterRequest;
use App\Http\Requests\RefugeeUpsertRequest;
use App\Services\FirebaseStorageService;
use App\Services\RefugeeRecordSync;
use App\Services\SigapDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class RefugeeController extends Controller
{
    public function __construct(
        protected SigapDataService $sigapDataService,
        protected RefugeeRepository $refugees,
        protected AuditTrailRepository $audits,
        protected DocumentRepository $documents,
        protected FirebaseStorageService $firebaseStorage,
        protected RefugeeRecordSync $recordSync
    ) {
    }

    public function index(RefugeeFilterRequest $request): View
    {
        $filters = $request->filters();

        $data = array_merge($this->baseViewData(), [
            'pageHeading' => 'Data Pengungsi',
            'pageDescription' => 'Cari, saring, dan kelola data pengungsi.',
            'refugees' => $this->sigapDataService->paginatedFilteredRefugees($filters),
            'filterOptions' => $this->sigapDataService->refugeeFilterOptions(),
            'activeFilters' => $filters,
        ]);

        return view('refugees.index', $data);
    }

    public function create(): View
    {
        $this->ensureAbility('manage-refugees');

        return view('refugees.create', array_merge($this->baseViewData(), $this->formViewData([
            'pageHeading' => 'Tambah Data Pengungsi',
            'pageDescription' => 'Isi identitas, status, lokasi hunian, dan catatan registrasi.',
            'refugee' => new Record(),
            'formAction' => route('refugees.store'),
            'formMethod' => 'POST',
        ])));
    }

    public function show(string $refugee): View
    {
        $record = $this->findOrFail($refugee);

        $data = array_merge($this->baseViewData(), [
            'pageHeading' => 'Detail Pengungsi',
            'pageDescription' => 'Profil lengkap, penempatan, dokumen, dan riwayat perubahan.',
            'refugee' => $record,
            'placement' => $this->sigapDataService->placementForRefugee($refugee),
            'document' => $this->sigapDataService->documentForRefugee($refugee),
            'history' => $this->sigapDataService->historyForRefugee($refugee),
        ]);

        return view('refugees.show', $data);
    }

    public function store(RefugeeUpsertRequest $request): RedirectResponse
    {
        $this->ensureAbility('manage-refugees');

        try {
            $refugee = $this->refugees->create($request->payload());
        } catch (FirebaseWriteException $e) {
            return back()->withInput()->withErrors(['internal_id' => $e->getMessage()]);
        }

        $this->audits->record([
            'refugee_id' => $refugee->id,
            'field_name' => 'Data pengungsi',
            'new_value' => $refugee->internal_id,
            'action_label' => 'Data pengungsi ditambahkan',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Input data baru',
        ]);

        $upload = $this->syncRelatedRecords($refugee, $request);

        return redirect()
            ->route('refugees.index')
            ->with('status', 'Data pengungsi berhasil ditambahkan.' . $upload['summary'])
            ->with('uploadErrors', $upload['errors']);
    }

    /**
     * Menyimpan penempatan dan dokumen yang diisi dari formulir data pengungsi.
     *
     * Data pengungsinya sudah tersimpan sebelum metode ini dipanggil, jadi
     * kegagalan di sini tidak boleh membatalkan apa pun. Yang gagal
     * dilaporkan apa adanya agar petugas tahu mana yang perlu diulang lewat
     * menu Penempatan atau Dokumen, alih-alih mengira semuanya sudah masuk.
     *
     * @return array{summary:string, errors:array<int, string>}
     */
    protected function syncRelatedRecords(Record $refugee, RefugeeUpsertRequest $request): array
    {
        $errors = [];

        try {
            $result = $this->recordSync->syncPlacement($refugee, $request->placementPayload());
            $placement = $result['placement'];

            if ($result['created'] || $result['old_location'] !== $placement->location_name) {
                $this->audits->record([
                    'refugee_id' => $refugee->id,
                    'field_name' => 'Penempatan',
                    'old_value' => $result['old_location'],
                    'new_value' => $placement->location_name,
                    'action_label' => $result['created'] ? 'Penempatan ditambahkan' : 'Penempatan diperbarui',
                    'performed_by_name' => $this->currentActorName(),
                    'reason' => 'Dicatat dari formulir data pengungsi',
                ]);
            }
        } catch (FirebaseWriteException $e) {
            $errors[] = 'Penempatan: ' . $e->getMessage();
        }

        $files = [];

        foreach ((array) $request->input('documents', []) as $index => $entry) {
            $file = $request->file("documents.{$index}.file");

            if ($file instanceof UploadedFile) {
                $files[(string) ($entry['type'] ?? 'Dokumen')] = $file;
            }
        }

        $upload = $this->recordSync->syncDocuments($refugee, $files, $this->currentActorName());
        $errors = array_merge($errors, $upload['errors']);

        $summary = match (true) {
            $upload['saved'] > 0 && $errors === [] => ' ' . $upload['saved'] . ' berkas dokumen ikut terunggah.',
            $upload['saved'] > 0 => ' ' . $upload['saved'] . ' berkas terunggah, sebagian data gagal disimpan.',
            $errors !== [] => ' Namun sebagian data penempatan atau dokumen gagal disimpan.',
            default => '',
        };

        return ['summary' => $summary, 'errors' => $errors];
    }

    public function edit(string $refugee): View
    {
        $this->ensureAbility('manage-refugees');
        $record = $this->findOrFail($refugee);

        return view('refugees.edit', array_merge($this->baseViewData(), $this->formViewData([
            'pageHeading' => 'Ubah Data Pengungsi',
            'pageDescription' => 'Perbarui identitas, status, lokasi hunian, dan catatan registrasi.',
            'refugee' => $record,
            'placement' => $this->recordSync->placementFor($refugee),
            'formAction' => route('refugees.update', $refugee),
            'formMethod' => 'PUT',
        ])));
    }

    public function update(RefugeeUpsertRequest $request, string $refugee): RedirectResponse
    {
        $this->ensureAbility('manage-refugees');
        $existing = $this->findOrFail($refugee);

        try {
            $updated = $this->refugees->update($refugee, $request->payload());
        } catch (FirebaseWriteException $e) {
            return back()->withInput()->withErrors(['internal_id' => $e->getMessage()]);
        }

        $this->audits->record([
            'refugee_id' => $refugee,
            'field_name' => 'Data pengungsi',
            'old_value' => $existing->internal_id,
            'new_value' => $updated->internal_id,
            'action_label' => 'Data pengungsi diperbarui',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Pembaruan data operasional',
        ]);

        $upload = $this->syncRelatedRecords($updated, $request);

        return redirect()
            ->route('refugees.index')
            ->with('status', 'Data pengungsi berhasil diperbarui.' . $upload['summary'])
            ->with('uploadErrors', $upload['errors']);
    }

    public function destroy(string $refugee): RedirectResponse
    {
        $this->ensureAbility('full-access');
        $existing = $this->findOrFail($refugee);

        try {
            $this->refugees->delete($refugee);
        } catch (FirebaseWriteException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        $this->audits->record([
            'refugee_id' => $refugee,
            'field_name' => 'Data pengungsi',
            'old_value' => $existing->internal_id,
            'action_label' => 'Data pengungsi dihapus',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Penghapusan data',
        ]);

        return redirect()
            ->route('refugees.index')
            ->with('status', 'Data pengungsi berhasil dihapus.');
    }

    protected function findOrFail(string $id): Record
    {
        $record = $this->refugees->find($id);

        abort_if($record === null, 404, 'Data pengungsi tidak ditemukan.');

        return $record;
    }

    protected function formViewData(array $overrides = []): array
    {
        $options = $this->sigapDataService->refugeeFilterOptions();

        return array_merge([
            'statusOptions' => $this->sigapDataService->refugeeStatusOptions(),
            'nationalityOptions' => $options['nationalities'],
            'locationOptions' => $this->sigapDataService->refugeeLocationOptions(),
            'documentStatusOptions' => $options['documentStatuses'],
            'documentTypeOptions' => $this->sigapDataService->documentTypeOptions(),
        ], $overrides);
    }
}
