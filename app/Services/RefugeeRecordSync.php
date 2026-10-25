<?php

namespace App\Services;

use App\Exceptions\DocumentUploadException;
use App\Exceptions\FirebaseWriteException;
use App\Firebase\DocumentRepository;
use App\Firebase\PlacementRepository;
use App\Firebase\Record;
use App\Firebase\RefugeeRepository;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Menjaga data pengungsi, penempatan, dan dokumennya tetap selaras.
 *
 * Ketiganya tersimpan di node Firebase yang terpisah. Dulu formulir Data
 * Pengungsi hanya menulis ke node pengungsi, sehingga pengungsi baru tidak
 * pernah muncul di menu Penempatan, dan baru muncul di menu Dokumen bila
 * berkasnya ikut diunggah. Seluruh penyelarasan kini terkumpul di sini.
 */
class RefugeeRecordSync
{
    public const LOKASI_MANDIRI = 'Pengungsi Mandiri';

    public function __construct(
        protected RefugeeRepository $refugees,
        protected PlacementRepository $placements,
        protected DocumentRepository $documents,
        protected FirebaseStorageService $firebaseStorage
    ) {
    }

    /**
     * Membuat atau memperbarui penempatan pengungsi dari formulir Data Pengungsi.
     *
     * Tanggal masuk, tanggal keluar, status, dan catatan penempatan yang sudah
     * ada tidak disentuh; yang diperbarui hanya letak tempat tinggalnya.
     *
     * @return array{placement: Record, created: bool, old_location: ?string}
     *
     * @throws FirebaseWriteException
     */
    public function syncPlacement(Record $refugee, array $location): array
    {
        $existing = $this->placementFor((string) $refugee->id);
        $payload = array_merge($location, ['refugee_id' => $refugee->id, 'refugee_name' => $refugee->name]);

        if ($existing !== null) {
            return [
                'placement' => $this->placements->update((string) $existing->id, $payload),
                'created' => false,
                'old_location' => $existing->location_name,
            ];
        }

        $statuses = config('sigap.reference.placement_statuses', []);

        return [
            'placement' => $this->placements->create(array_merge($payload, [
                'entered_at' => $refugee->registered_at ?? now()->toDateString(),
                'placement_status' => in_array($refugee->status, $statuses, true) ? $refugee->status : 'Aktif',
            ])),
            'created' => true,
            'old_location' => null,
        ];
    }

    /**
     * Menyamakan kolom Lokasi Aktif pengungsi dengan penempatannya.
     *
     * Dipanggil setelah penempatan diubah lewat menu Penempatan, supaya daftar
     * Data Pengungsi tidak menampilkan lokasi lama.
     */
    public function syncRefugeeLocation(Record $placement): void
    {
        $refugeeId = (string) $placement->refugee_id;

        if ($refugeeId === '' || $this->refugees->find($refugeeId) === null) {
            return;
        }

        $location = $placement->category === 'mandiri'
            ? self::LOKASI_MANDIRI
            : (string) $placement->community_house;

        if (! in_array($location, config('sigap.reference.refugee_locations', []), true)) {
            return;
        }

        try {
            $this->refugees->update($refugeeId, ['location' => $location]);
        } catch (FirebaseWriteException) {
            // Ringkasan ini turunan; penempatannya sendiri sudah tersimpan.
        }
    }

    /**
     * Menyimpan berkas dari formulir Data Pengungsi, lalu memastikan setiap
     * jenis dokumen wajib punya catatan di menu Dokumen.
     *
     * Berkas untuk jenis yang sudah tercatat menggantikan berkas lamanya,
     * bukan menambah catatan kembar. Jenis yang belum diunggah dicatat
     * "Belum Lengkap" supaya kekurangannya terlihat dan dapat dilengkapi
     * lewat tombol Ubah di menu Dokumen.
     *
     * @param  array<string, UploadedFile>  $files  berkas per jenis dokumen
     * @return array{saved: int, errors: array<int, string>}
     */
    public function syncDocuments(Record $refugee, array $files, string $actor): array
    {
        $saved = 0;
        $errors = [];

        foreach ($files as $type => $file) {
            try {
                $stored = $this->firebaseStorage->storeDocument($file, $type);
                $existing = $this->documentFor((string) $refugee->id, $type);

                $payload = [
                    'refugee_id' => $refugee->id,
                    'refugee_name' => $refugee->name,
                    'document_type' => $type,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $stored['path'],
                    'download_url' => $stored['download_url'] ?? null,
                    'drive_file_id' => $stored['firebase_document_key'],
                    'storage_key' => $stored['storage_key'] ?? null,
                    'verification_status' => 'Perlu Verifikasi',
                    'uploaded_at' => now()->toIso8601String(),
                    'uploaded_by' => $actor,
                ];

                if ($existing !== null) {
                    $this->documents->update((string) $existing->id, $payload);
                    $this->firebaseStorage->forgetFromRealtimeDatabase($existing->storage_key);
                } else {
                    $this->documents->create($payload + [
                        'notes' => 'Diunggah bersamaan dengan pendaftaran data pengungsi.',
                    ]);
                }

                $saved++;
            } catch (DocumentUploadException | FirebaseWriteException $e) {
                $errors[] = $type . ': ' . $e->getMessage();
            } catch (Throwable $e) {
                $errors[] = $type . ': berkas gagal disimpan.';
            }
        }

        foreach (config('sigap.reference.document_types', []) as $type) {
            if ($this->documentFor((string) $refugee->id, $type) !== null) {
                continue;
            }

            try {
                $this->documents->create([
                    'refugee_id' => $refugee->id,
                    'refugee_name' => $refugee->name,
                    'document_type' => $type,
                    'verification_status' => 'Belum Lengkap',
                    'uploaded_at' => now()->toIso8601String(),
                    'uploaded_by' => $actor,
                    'notes' => 'Berkas belum diunggah.',
                ]);
            } catch (FirebaseWriteException $e) {
                $errors[] = $type . ': ' . $e->getMessage();
            }
        }

        $this->syncDocumentStatus((string) $refugee->id);

        return ['saved' => $saved, 'errors' => $errors];
    }

    /**
     * Menyelaraskan status kelengkapan dokumen pada data pengungsi.
     *
     * Lengkap bila setiap jenis dokumen wajib punya berkas yang sudah
     * diterima Supervisor, Perlu Verifikasi bila semuanya ada tetapi masih
     * ada yang menunggu pemeriksaan, selain itu Belum Lengkap.
     */
    public function syncDocumentStatus(string $refugeeId): void
    {
        if ($refugeeId === '' || $this->refugees->find($refugeeId) === null) {
            return;
        }

        $berkas = $this->documents->all()
            ->filter(fn (Record $item) => (string) $item->refugee_id === $refugeeId)
            ->groupBy(fn (Record $item) => (string) $item->document_type)
            ->map(function ($items) {
                $statuses = $items->pluck('verification_status')->all();

                return match (true) {
                    in_array('Lengkap', $statuses, true) => 'Lengkap',
                    in_array('Perlu Verifikasi', $statuses, true) => 'Perlu Verifikasi',
                    default => 'Belum Lengkap',
                };
            });

        $wajib = config('sigap.reference.document_types', []);
        $statuses = collect($wajib)->map(fn (string $type) => $berkas->get($type, 'Belum Lengkap'));

        $status = match (true) {
            $statuses->contains('Belum Lengkap') => 'Belum Lengkap',
            $statuses->contains('Perlu Verifikasi') => 'Perlu Verifikasi',
            default => 'Lengkap',
        };

        try {
            $this->refugees->update($refugeeId, ['document_status' => $status]);
        } catch (FirebaseWriteException) {
            // Ringkasan ini turunan; dokumennya sendiri sudah tersimpan.
        }
    }

    public function placementFor(string $refugeeId): ?Record
    {
        return $this->placements->all()
            ->first(fn (Record $item) => (string) $item->refugee_id === $refugeeId);
    }

    protected function documentFor(string $refugeeId, string $type): ?Record
    {
        return $this->documents->all()->first(
            fn (Record $item) => (string) $item->refugee_id === $refugeeId
                && (string) $item->document_type === $type
        );
    }
}
