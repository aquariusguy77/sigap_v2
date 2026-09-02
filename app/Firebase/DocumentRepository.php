<?php

namespace App\Firebase;

class DocumentRepository extends Repository
{
    protected string $node = 'documents';

    protected array $fields = [
        'refugee_id',
        'refugee_name',
        'document_type',
        'file_name',
        'file_path',
        'download_url',
        'drive_file_id',
        // Kunci node berkas pada mode penyimpanan "rtdb".
        'storage_key',
        'verification_status',
        'uploaded_at',
        'uploaded_by',
        'notes',
    ];

    protected array $dates = ['uploaded_at'];

    protected string $sortBy = 'uploaded_at';

    protected function decorate(array $payload): array
    {
        $payload['document_type'] = $payload['document_type'] ?? 'Dokumen';
        $payload['name'] = $payload['document_type'];
        $payload['verification_status'] = $payload['verification_status'] ?? 'Belum Lengkap';
        $payload['status'] = $payload['verification_status'];
        $payload['storage'] = $payload['file_path'] ?? '-';
        $payload['meta'] = trim(
            ($payload['refugee_name'] ?? 'Tanpa pengungsi') . ' • ' . ($payload['file_name'] ?? '-')
        );

        return $payload;
    }
}
