<?php

namespace App\Services;

use App\Firebase\Record;
use Illuminate\Support\Collection;

/**
 * Menyusun isi laporan yang dapat diunduh petugas, dalam bentuk CSV maupun PDF.
 */
class ReportExportService
{
    public function __construct(
        protected SigapDataService $data
    ) {
    }

    public function definitions(): array
    {
        return [
            'pengungsi' => [
                'name' => 'Rekap Data Pengungsi',
                'note' => 'Seluruh data pengungsi beserta kebangsaan, status, lokasi, dan kelengkapan dokumen.',
                'icon' => 'users',
            ],
            'dokumen' => [
                'name' => 'Laporan Dokumen',
                'note' => 'Daftar berkas pendukung beserta status verifikasi dan tanggal unggahnya.',
                'icon' => 'folder',
            ],
            'penempatan' => [
                'name' => 'Laporan Penempatan',
                'note' => 'Kategori penempatan, Community House atau alamat mandiri, tanggal, dan status.',
                'icon' => 'location',
            ],
            'riwayat' => [
                'name' => 'Riwayat Perubahan',
                'note' => 'Catatan perubahan data lengkap dengan pelaksana dan waktu kejadian.',
                'icon' => 'history',
            ],
            'prioritas' => [
                'name' => 'Prioritas Verifikasi',
                'note' => 'Data yang dokumennya belum lengkap dan perlu ditindaklanjuti supervisor.',
                'icon' => 'alert',
            ],
        ];
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->definitions());
    }

    public function name(string $key): string
    {
        return $this->definitions()[$key]['name'] ?? 'Laporan';
    }

    public function note(string $key): string
    {
        return $this->definitions()[$key]['note'] ?? '';
    }

    /**
     * Laporan berkolom banyak dicetak melintang agar tetap terbaca.
     */
    public function orientation(string $key): string
    {
        return count($this->headings($key)) > 5 ? 'landscape' : 'portrait';
    }

    public function headings(string $key): array
    {
        return match ($key) {
            'dokumen' => ['Jenis Dokumen', 'Pengungsi', 'Nama Berkas', 'Status Verifikasi', 'Tanggal Unggah'],
            'penempatan' => ['Pengungsi', 'Kategori', 'Lokasi / Alamat', 'Masuk', 'Keluar', 'Status'],
            'riwayat' => ['Aktivitas', 'Rincian Perubahan', 'Pelaksana', 'Waktu'],
            'prioritas' => ['ID Internal', 'Nama', 'Kebangsaan', 'Lokasi', 'Kelengkapan Dokumen'],
            default => ['ID Internal', 'Nama', 'Kebangsaan', 'Nomor UNHCR', 'Status', 'Lokasi', 'Kelengkapan Dokumen'],
        };
    }

    public function rows(string $key): Collection
    {
        return match ($key) {
            'dokumen' => $this->documentRows(),
            'penempatan' => $this->placementRows(),
            'riwayat' => $this->historyRows(),
            'prioritas' => $this->priorityRows(),
            default => $this->refugeeRows(),
        };
    }

    protected function refugeeRows(): Collection
    {
        return $this->data->refugees()->map(fn (Record $item) => [
            $item->internal_id ?: '-',
            $item->name ?: '-',
            $item->nationality ?: '-',
            $item->unhcr_number ?: '-',
            $item->status ?: '-',
            $item->location ?: '-',
            $item->document_status ?: '-',
        ])->values();
    }

    protected function documentRows(): Collection
    {
        return $this->data->documents()->map(fn (Record $item) => [
            $item->document_type ?: '-',
            $item->refugee_name ?: '-',
            $item->file_name ?: '-',
            $item->verification_status ?: '-',
            $this->date($item->uploaded_at),
        ])->values();
    }

    protected function placementRows(): Collection
    {
        return $this->data->placements()->map(fn (Record $item) => [
            $item->refugee_name ?: '-',
            $item->category_label ?: '-',
            // Community House bagi yang difasilitasi IOM, alamat bagi yang mandiri.
            $item->is_mandiri ? ($item->address ?: '-') : ($item->community_house ?: '-'),
            $this->date($item->entered_at),
            $this->date($item->exited_at),
            $item->placement_status ?: '-',
        ])->values();
    }

    protected function historyRows(): Collection
    {
        return $this->data->history()->map(fn (Record $item) => [
            $item->title ?: '-',
            $item->detail ?: '-',
            $item->actor ?: '-',
            $item->time ?: '-',
        ])->values();
    }

    protected function priorityRows(): Collection
    {
        return $this->data->refugees()
            ->filter(fn (Record $item) => in_array($item->document_status, ['Perlu Verifikasi', 'Belum Lengkap'], true))
            ->map(fn (Record $item) => [
                $item->internal_id ?: '-',
                $item->name ?: '-',
                $item->nationality ?: '-',
                $item->location ?: '-',
                $item->document_status ?: '-',
            ])
            ->values();
    }

    protected function date(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        return $value instanceof \DateTimeInterface
            ? $value->format('d/m/Y')
            : (string) $value;
    }

    public function fileName(string $key, string $extension): string
    {
        return 'sigap-' . $key . '-' . now()->format('Ymd-Hi') . '.' . $extension;
    }

    /**
     * Menyusun berkas CSV.
     *
     * Diawali BOM UTF-8 dan penanda "sep=;" supaya Excel membuka berkas dengan
     * kolom yang sudah terpisah rapi dan huruf beraksen tidak rusak, apa pun
     * pengaturan wilayah pada komputer petugas.
     */
    public function toCsv(string $key): string
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");
        fwrite($handle, "sep=;\n");
        fputcsv($handle, $this->headings($key), ';');

        foreach ($this->rows($key) as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $contents;
    }
}
