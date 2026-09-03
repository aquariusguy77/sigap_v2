<?php

namespace App\Http\Controllers;

use App\Exceptions\DocumentUploadException;
use App\Exceptions\FirebaseWriteException;
use App\Firebase\AuditTrailRepository;
use App\Firebase\DocumentRepository;
use App\Firebase\Record;
use App\Firebase\RefugeeRepository;
use App\Http\Requests\RefugeeDocumentUpsertRequest;
use App\Services\FirebaseStorageService;
use App\Services\SigapDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        protected SigapDataService $sigapDataService,
        protected DocumentRepository $documents,
        protected RefugeeRepository $refugees,
        protected AuditTrailRepository $audits,
        protected FirebaseStorageService $firebaseStorage
    ) {
    }

    public function index(): View
    {
        $filters = [
            'keyword' => trim((string) request('keyword', '')),
            'status' => (string) request('status', ''),
            'type' => (string) request('type', ''),
            'per_page' => max(5, min((int) request('per_page', 10), 20)),
        ];

        return view('documents.index', array_merge($this->baseViewData(), [
            'pageHeading' => 'Dokumen',
            'pageDescription' => 'Berkas pendukung pengungsi beserta status verifikasinya.',
            'documents' => $this->sigapDataService->paginatedFilteredDocuments($filters),
            'activeFilters' => $filters,
            'statusOptions' => $this->sigapDataService->documentStatusOptions(),
            'documentTypes' => $this->sigapDataService->documentTypeOptions(),
        ]));
    }

    public function create(): View
    {
        $this->ensureAbility('manage-documents');

        return view('documents.create', array_merge($this->baseViewData(), [
            'pageHeading' => 'Tambah Dokumen',
            'pageDescription' => 'Unggah berkas dan lengkapi keterangan dokumen.',
            'document' => new Record(),
            'formAction' => route('documents.store'),
            'formMethod' => 'POST',
            'refugees' => $this->sigapDataService->refugeeSelectOptions(),
            'documentTypes' => $this->sigapDataService->documentTypeOptions(),
            'statusOptions' => $this->sigapDataService->documentStatusOptions(),
        ]));
    }

    public function show(string $document): View
    {
        $record = $this->findOrFail($document);

        return view('documents.show', array_merge($this->baseViewData(), [
            'pageHeading' => 'Detail Dokumen',
            'pageDescription' => 'Rincian berkas dan status verifikasinya.',
            'document' => $record,
            'documentView' => $record,
            'refugeeView' => $this->sigapDataService->refugeeById((string) $record->refugee_id),
        ]));
    }

    public function store(RefugeeDocumentUpsertRequest $request): RedirectResponse
    {
        $this->ensureAbility('manage-documents');

        try {
            $payload = $this->resolvePayload($request);
            $document = $this->documents->create($payload);
        } catch (DocumentUploadException $e) {
            return back()->withInput()->withErrors(['uploaded_file' => $e->getMessage()]);
        } catch (FirebaseWriteException $e) {
            return back()->withInput()->withErrors(['document_type' => $e->getMessage()]);
        }

        $this->audits->record([
            'refugee_id' => $document->refugee_id,
            'field_name' => 'Dokumen',
            'new_value' => $document->document_type,
            'action_label' => 'Dokumen ditambahkan',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Unggah dokumen baru',
        ]);

        return redirect()
            ->route('documents.index')
            ->with('status', 'Dokumen berhasil disimpan.');
    }

    public function edit(string $document): View
    {
        $this->ensureAbility('manage-documents');

        return view('documents.edit', array_merge($this->baseViewData(), [
            'pageHeading' => 'Ubah Dokumen',
            'pageDescription' => 'Perbarui keterangan berkas dan status verifikasinya.',
            'document' => $this->findOrFail($document),
            'formAction' => route('documents.update', $document),
            'formMethod' => 'PUT',
            'refugees' => $this->sigapDataService->refugeeSelectOptions(),
            'documentTypes' => $this->sigapDataService->documentTypeOptions(),
            'statusOptions' => $this->sigapDataService->documentStatusOptions(),
        ]));
    }

    public function update(RefugeeDocumentUpsertRequest $request, string $document): RedirectResponse
    {
        $this->ensureAbility('manage-documents');
        $existing = $this->findOrFail($document);

        try {
            $payload = $this->resolvePayload($request, $existing);
            $updated = $this->documents->update($document, $payload);
        } catch (DocumentUploadException $e) {
            return back()->withInput()->withErrors(['uploaded_file' => $e->getMessage()]);
        } catch (FirebaseWriteException $e) {
            return back()->withInput()->withErrors(['document_type' => $e->getMessage()]);
        }

        $this->audits->record([
            'refugee_id' => $updated->refugee_id,
            'field_name' => 'Dokumen',
            'old_value' => $existing->verification_status,
            'new_value' => $updated->verification_status,
            'action_label' => 'Dokumen diperbarui',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Pembaruan keterangan dokumen',
        ]);

        return redirect()
            ->route('documents.index')
            ->with('status', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Mengirimkan berkas yang tersimpan di Realtime Database.
     *
     * Berkas tidak berada di URL publik mana pun, sehingga hanya bisa diambil
     * lewat rute ini — dan rute ini berada di dalam kelompok yang mewajibkan
     * pengguna sudah masuk.
     */
    public function file(string $berkas): StreamedResponse
    {
        $this->ensureAbility('view-reports');

        $stored = $this->firebaseStorage->fetchFromRealtimeDatabase($berkas);

        abort_if($stored === null, 404, 'Berkas dokumen tidak ditemukan.');

        return response()->streamDownload(
            fn () => print($stored['contents']),
            $stored['file_name'],
            [
                'Content-Type' => $stored['mime_type'],
                'Content-Length' => (string) strlen($stored['contents']),
                'Cache-Control' => 'no-store',
            ]
        );
    }

    public function destroy(string $document): RedirectResponse
    {
        $this->ensureAbility('full-access');
        $existing = $this->findOrFail($document);

        try {
            $this->documents->delete($document);
        } catch (FirebaseWriteException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        // Berkasnya ikut dibuang agar tidak menumpuk tanpa pemilik di Firebase.
        $this->firebaseStorage->forgetFromRealtimeDatabase($existing->storage_key);

        $this->audits->record([
            'refugee_id' => $existing->refugee_id,
            'field_name' => 'Dokumen',
            'old_value' => $existing->document_type,
            'action_label' => 'Dokumen dihapus',
            'performed_by_name' => $this->currentActorName(),
            'reason' => 'Penghapusan dokumen',
        ]);

        return redirect()
            ->route('documents.index')
            ->with('status', 'Dokumen berhasil dihapus.');
    }

    /**
     * Menyusun data dokumen, termasuk mengunggah berkas bila ada.
     *
     * @throws DocumentUploadException
     */
    protected function resolvePayload(RefugeeDocumentUpsertRequest $request, ?Record $existing = null): array
    {
        $payload = $request->payload();
        $file = $request->file('uploaded_file');

        if ($file instanceof UploadedFile) {
            $stored = $this->firebaseStorage->storeDocument($file, (string) ($payload['document_type'] ?? 'dokumen'));

            /*
             * Lokasi berkas dan kode referensinya tidak lagi diketik petugas.
             * Keduanya ditentukan sendiri oleh lapisan penyimpanan, jadi cukup
             * disalin dari hasil unggahan.
             */
            $payload['file_name'] = $file->getClientOriginalName();
            $payload['file_path'] = $stored['path'];
            $payload['download_url'] = $stored['download_url'] ?? null;
            $payload['drive_file_id'] = $stored['firebase_document_key'];
            $payload['storage_key'] = $stored['storage_key'] ?? null;

            // Berkas lama tidak lagi dirujuk siapa pun, jadi tidak perlu disimpan.
            if ($existing !== null) {
                $this->firebaseStorage->forgetFromRealtimeDatabase($existing->storage_key);
            }
        } elseif ($existing !== null) {
            // Tanpa berkas baru, keterangan berkas yang lama dipertahankan.
            $payload['file_name'] = $payload['file_name'] ?: $existing->file_name;
            $payload['file_path'] = $existing->file_path;
            $payload['download_url'] = $existing->download_url;
            $payload['drive_file_id'] = $existing->drive_file_id;
            $payload['storage_key'] = $existing->storage_key;
        }

        $refugee = filled($payload['refugee_id'] ?? null)
            ? $this->refugees->find((string) $payload['refugee_id'])
            : null;
        $payload['refugee_name'] = $refugee?->name;
        $payload['uploaded_by'] = $this->currentActorName();

        return $payload;
    }

    protected function findOrFail(string $id): Record
    {
        $record = $this->documents->find($id);

        abort_if($record === null, 404, 'Dokumen tidak ditemukan.');

        return $record;
    }
}
