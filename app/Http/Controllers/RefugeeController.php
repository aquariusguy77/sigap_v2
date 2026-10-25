<?php

namespace App\Http\Controllers;

use App\Exceptions\FirebaseWriteException;
use App\Firebase\AuditTrailRepository;
use App\Firebase\DocumentRepository;
use App\Firebase\PlacementRepository;
use App\Firebase\Record;
use App\Firebase\RefugeeRepository;
use App\Http\Requests\RefugeeFilterRequest;
use App\Http\Requests\RefugeeUpsertRequest;
use App\Services\FirebaseStorageService;
use App\Services\RefugeeRecordSync;
use App\Services\RoleAccessService;
use App\Services\SigapDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
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
            // Data baru selalu menunggu pemeriksaan Supervisor.
            $refugee = $this->refugees->create($request->payload() + ['status' => 'Perlu Verifikasi']);
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

        $payload = $request->payload();

        /*
         * Data yang isinya berubah kembali menunggu pemeriksaan Supervisor.
         * Menyimpan ulang tanpa perubahan, atau sekadar mengunggah berkas
         * yang punya verifikasinya sendiri, tidak mengubah status.
         */
        if ($this->dataChanged($existing, $request)) {
            $payload['status'] = 'Perlu Verifikasi';
        }

        try {
            $updated = $this->refugees->update($refugee, $payload);
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

    /**
     * Keputusan Supervisor atas data pengungsi: diterima atau dikembalikan.
     */
    public function verify(Request $request, string $refugee): RedirectResponse
    {
        // Khusus Supervisor; full-access milik Admin sengaja tidak berlaku di sini.
        abort_unless(
            app(RoleAccessService::class)->holds('verify-refugees'),
            403,
            'Verifikasi data pengungsi hanya dapat dilakukan Supervisor.'
        );
        $existing = $this->findOrFail($refugee);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['Aktif', 'Perlu Verifikasi'])],
            'verification_note' => ['nullable', 'required_if:decision,Perlu Verifikasi', 'string', 'max:1000'],
        ], [
            'decision.in' => 'Pilih Terima atau Kembalikan.',
            'verification_note.required_if' => 'Tuliskan bagian yang perlu diperbaiki agar petugas tahu apa yang harus diubah.',
        ]);

        $diterima = $validated['decision'] === 'Aktif';

        try {
            $updated = $this->refugees->update($refugee, [
                'status' => $validated['decision'],
                'verification_note' => $diterima ? null : $validated['verification_note'],
                'verified_by' => $this->currentActorName(),
                'verified_at' => now()->toIso8601String(),
            ]);
        } catch (FirebaseWriteException $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        // Lokasi hunian ikut diperiksa, jadi penempatannya ikut aktif bila letaknya sudah tercatat.
        $placement = $this->recordSync->placementFor($refugee);

        if ($diterima && $placement !== null && $placement->placement_status === 'Perlu Verifikasi'
            && ($placement->category !== 'mandiri' || filled($placement->address))) {
            try {
                app(PlacementRepository::class)->update((string) $placement->id, ['placement_status' => 'Aktif']);
            } catch (FirebaseWriteException) {
                // Status penempatan masih dapat diubah lewat menu Penempatan.
            }
        }

        $this->audits->record([
            'refugee_id' => $refugee,
            'refugee_name' => $existing->name,
            'field_name' => 'Status data',
            'old_value' => $existing->status,
            'new_value' => $updated->status,
            'action_label' => $diterima ? 'Data pengungsi diverifikasi' : 'Data pengungsi dikembalikan',
            'performed_by_name' => $this->currentActorName(),
            'reason' => $diterima ? 'Pemeriksaan data oleh supervisor' : $validated['verification_note'],
        ]);

        return redirect()
            ->route('refugees.show', $refugee)
            ->with('status', $diterima
                ? 'Data pengungsi dinyatakan Aktif.'
                : 'Data pengungsi dikembalikan ke petugas untuk diperbaiki.');
    }

    /**
     * Apakah isi data pengungsi atau letak tempat tinggalnya berubah.
     */
    protected function dataChanged(Record $existing, RefugeeUpsertRequest $request): bool
    {
        $normal = fn ($value) => $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : trim((string) $value);

        foreach ($request->payload() as $field => $value) {
            if ($normal($value) !== $normal($existing->{$field})) {
                return true;
            }
        }

        $placement = $this->recordSync->placementFor((string) $existing->id);

        foreach ($request->placementPayload() as $field => $value) {
            $old = $placement?->{$field};

            // Koordinat dibandingkan sebagai angka: "-7.28" sama dengan "-7.280000".
            if (in_array($field, ['latitude', 'longitude'], true) && is_numeric($value) && is_numeric($old)) {
                if (abs((float) $value - (float) $old) > 0.0000005) {
                    return true;
                }

                continue;
            }

            if ($normal($value) !== $normal($old)) {
                return true;
            }
        }

        return false;
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
            'refugee_name' => $existing->name,
            'field_name' => 'Data pengungsi',
            'old_value' => $existing->internal_id,
            'action_label' => 'Data pengungsi dihapus',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Penghapusan data',
        ]);

        // Penempatan dan dokumennya ikut dihapus agar tidak tertinggal di menu dan laporan.
        $errors = $this->recordSync->deleteRelated($refugee);

        return redirect()
            ->route('refugees.index')
            ->with('status', 'Data pengungsi berhasil dihapus.')
            ->with('uploadErrors', $errors);
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
            'nationalityOptions' => $options['nationalities'],
            'locationOptions' => $this->sigapDataService->refugeeLocationOptions(),
            'documentStatusOptions' => $options['documentStatuses'],
            'documentTypeOptions' => $this->sigapDataService->documentTypeOptions(),
        ], $overrides);
    }
}
