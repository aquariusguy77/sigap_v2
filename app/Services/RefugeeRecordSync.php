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

        // Nama yang diubah ikut diperbarui pada dokumen yang tidak diunggah ulang.
        foreach ($this->documentsFor((string) $refugee->id) as $document) {
            if ($document->refugee_name !== $refugee->name) {
                try {
                    $this->documents->update((string) $document->id, ['refugee_name' => $refugee->name]);
                } catch (FirebaseWriteException) {
                    // Hanya salinan nama; dokumennya sendiri tetap utuh.
                }
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

    /**
     * Menghapus penempatan dan dokumen milik pengungsi yang dihapus.
     *
     * Tanpa ini, pengungsi yang sudah dihapus tetap tercantum di menu dan
     * laporan Penempatan maupun Dokumen.
     *
     * @return array<int, string> pesan kegagalan, bila ada
     */
    public function deleteRelated(string $refugeeId): array
    {
        $errors = [];

        $placements = $this->placements->all()->filter(fn (Record $item) => (string) $item->refugee_id === $refugeeId);

        foreach ($placements as $placement) {
            try {
                $this->placements->delete((string) $placement->id);
            } catch (FirebaseWriteException $e) {
                $errors[] = 'Penempatan: ' . $e->getMessage();
            }
        }

        foreach ($this->documentsFor($refugeeId) as $document) {
            try {
                $this->documents->delete((string) $document->id);
                $this->firebaseStorage->forgetFromRealtimeDatabase($document->storage_key);
            } catch (FirebaseWriteException $e) {
                $errors[] = 'Dokumen: ' . $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Melengkapi data pengungsi yang tercatat sebelum penyelarasan ini ada.
     *
     * Pengungsi lama tidak punya penempatan maupun catatan dokumen, sehingga
     * tidak muncul di menu dan laporan Penempatan maupun Dokumen. Penempatan
     * dibuat dari Lokasi Aktif-nya; pengungsi mandiri belum punya alamat,
     * jadi penempatannya ditandai Perlu Verifikasi agar segera dilengkapi.
     *
     * Ketiga node dibaca sekali saja di awal. Membacanya ulang untuk setiap
     * pengungsi akan mengunduh seluruh node berkali-kali, dan pada Vercel
     * permintaan semacam itu dapat melewati batas waktu.
     *
     * @return array{placements: int, documents: int, errors: array<int, string>}
     */
    public function backfill(string $actor): array
    {
        $placedIds = $this->placements->all()->pluck('refugee_id')->map(fn ($id) => (string) $id)->flip();
        $documentsByRefugee = $this->documents->all()->groupBy(fn (Record $item) => (string) $item->refugee_id);
        $wajib = config('sigap.reference.document_types', []);
        $houses = config('sigap.reference.community_houses', []);

        $created = ['placements' => 0, 'documents' => 0, 'errors' => []];

        foreach ($this->refugees->all() as $refugee) {
            $id = (string) $refugee->id;

            try {
                if (! $placedIds->has($id)) {
                    $mandiri = $refugee->location === self::LOKASI_MANDIRI;
                    $house = in_array($refugee->location, $houses, true) ? $refugee->location : null;

                    if ($mandiri || $house !== null) {
                        $this->placements->create([
                            'refugee_id' => $id,
                            'refugee_name' => $refugee->name,
                            'category' => $mandiri ? 'mandiri' : 'iom',
                            'community_house' => $house,
                            'entered_at' => $refugee->registered_at ?? now()->toDateString(),
                            'placement_status' => $mandiri ? 'Perlu Verifikasi' : 'Aktif',
                            'notes' => $mandiri ? 'Alamat belum dicatat. Lengkapi lewat tombol Ubah.' : null,
                        ]);
                        $created['placements']++;
                    }
                }

                $existing = ($documentsByRefugee->get($id) ?? collect())
                    ->pluck('document_type')->map(fn ($type) => (string) $type)->all();
                $statuses = ($documentsByRefugee->get($id) ?? collect())
                    ->groupBy(fn (Record $item) => (string) $item->document_type)
                    ->map(fn ($items) => $items->pluck('verification_status')->all());

                foreach (array_diff($wajib, $existing) as $type) {
                    $this->documents->create([
                        'refugee_id' => $id,
                        'refugee_name' => $refugee->name,
                        'document_type' => $type,
                        'verification_status' => 'Belum Lengkap',
                        'uploaded_at' => now()->toIso8601String(),
                        'uploaded_by' => $actor,
                        'notes' => 'Berkas belum diunggah.',
                    ]);
                    $created['documents']++;
                }

                $perJenis = collect($wajib)->map(function (string $type) use ($statuses) {
                    $list = $statuses->get($type, []);

                    return match (true) {
                        in_array('Lengkap', $list, true) => 'Lengkap',
                        in_array('Perlu Verifikasi', $list, true) => 'Perlu Verifikasi',
                        default => 'Belum Lengkap',
                    };
                });

                $status = match (true) {
                    $perJenis->contains('Belum Lengkap') => 'Belum Lengkap',
                    $perJenis->contains('Perlu Verifikasi') => 'Perlu Verifikasi',
                    default => 'Lengkap',
                };

                if ($refugee->document_status !== $status) {
                    $this->refugees->update($id, ['document_status' => $status]);
                }
            } catch (FirebaseWriteException $e) {
                $created['errors'][] = ($refugee->name ?: $id) . ': ' . $e->getMessage();
            }
        }

        return $created;
    }

    public function placementFor(string $refugeeId): ?Record
    {
        return $this->placements->all()
            ->first(fn (Record $item) => (string) $item->refugee_id === $refugeeId);
    }

    protected function documentsFor(string $refugeeId): \Illuminate\Support\Collection
    {
        return $this->documents->all()->filter(fn (Record $item) => (string) $item->refugee_id === $refugeeId);
    }

    protected function documentFor(string $refugeeId, string $type): ?Record
    {
        return $this->documents->all()->first(
            fn (Record $item) => (string) $item->refugee_id === $refugeeId
                && (string) $item->document_type === $type
        );
    }
}
