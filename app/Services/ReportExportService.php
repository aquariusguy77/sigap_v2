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
     * Seluruh laporan dicetak tegak.
     *
     * Sebelumnya laporan berkolom banyak otomatis dicetak melintang, sehingga
     * satu berkas laporan bisa berisi halaman dengan orientasi berbeda-beda —
     * merepotkan saat dijilid maupun dilampirkan. Agar tetap terbaca, kolom
     * yang banyak ditangani lewat ukuran huruf pada template, bukan dengan
     * memutar kertasnya.
     */
    public function orientation(string $key): string
    {
        return 'portrait';
    }

    public function headings(string $key): array
    {
        return match ($key) {
            'dokumen' => ['Jenis Dokumen', 'Pengungsi', 'Nama Berkas', 'Status Verifikasi', 'Tanggal Unggah'],
            'penempatan' => ['Pengungsi', 'Kategori', 'Lokasi / Alamat', 'Titik Koordinat', 'Masuk', 'Keluar', 'Status'],
            'riwayat' => ['Aktivitas', 'Pengungsi', 'Rincian Perubahan', 'Pelaksana', 'Waktu'],
            'prioritas' => ['ID Internal', 'Nama', 'Kebangsaan', 'Lokasi', 'Kelengkapan'],
            default => [
                'ID Internal',
                'Nama',
                'Kebangsaan',
                'Nomor UNHCR',
                'Telepon',
                'Status',
                'Lokasi',
                'Kelengkapan',
            ],
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

    /**
     * Banyaknya baris sebuah laporan, untuk angka pada kartu di halaman
     * Laporan.
     *
     * Dipisahkan dari rows() karena halaman itu hanya memerlukan angkanya.
     * Memanggil rows()->count() berarti menyusun seluruh isi kelima laporan
     * lebih dulu — pada basis data dengan 3.000 riwayat, 1,6 MB diunduh
     * hanya untuk menampilkan lima bilangan.
     *
     * Empat laporan memetakan satu node apa adanya, tanpa penyaringan, jadi
     * jumlahnya dapat dihitung di sisi Firebase tanpa mengunduh isinya.
     * Hanya "prioritas" yang menyaring — hanya pengungsi yang dokumennya
     * belum lengkap — sehingga isinya memang perlu dibaca.
     */
    public function countFor(string $key): int
    {
        return match ($key) {
            'dokumen' => $this->data->countOf('documents'),
            'penempatan' => $this->data->countOf('placements'),
            'riwayat' => $this->data->countOf('history'),
            'prioritas' => $this->priorityRows()->count(),
            default => $this->data->countOf('refugees'),
        };
    }

    protected function refugeeRows(): Collection
    {
        $addresses = $this->mandiriAddresses();

        return $this->data->refugees()->map(fn (Record $item) => [
            $item->internal_id ?: '-',
            $item->name ?: '-',
            $item->nationality ?: '-',
            $item->unhcr_number ?: '-',
            $item->phone ?: '-',
            $item->status ?: '-',
            $this->locationLabel($item, $addresses),
            $item->document_status ?: '-',
        ])->values();
    }

    /**
     * Alamat pengungsi mandiri per id pengungsi, diambil dari penempatannya.
     *
     * @return Collection<string, string>
     */
    protected function mandiriAddresses(): Collection
    {
        return $this->data->placements()
            ->filter(fn (Record $item) => $item->is_mandiri && filled($item->address))
            ->mapWithKeys(fn (Record $item) => [(string) $item->refugee_id => (string) $item->address]);
    }

    /**
     * "Pengungsi Mandiri" saja tidak memberi tahu di mana orangnya tinggal,
     * jadi alamatnya ikut dicantumkan.
     */
    protected function locationLabel(Record $item, Collection $addresses): string
    {
        $location = $item->location ?: '-';
        $address = $addresses->get((string) $item->id);

        return $address ? $location . ' — ' . $address : $location;
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
            // Bisa ditempel langsung ke kolom pencarian Google Maps.
            filled($item->latitude) && filled($item->longitude) ? $item->latitude . ', ' . $item->longitude : '-',
            $this->date($item->entered_at),
            $this->date($item->exited_at),
            $item->placement_status ?: '-',
        ])->values();
    }

    protected function historyRows(): Collection
    {
        // Riwayat lama belum menyimpan nama; diambil dari data pengungsi yang masih ada.
        $names = $this->data->refugees()->mapWithKeys(fn (Record $item) => [(string) $item->id => (string) $item->name]);

        return $this->data->history()->map(fn (Record $item) => [
            $item->title ?: '-',
            $item->refugee_name ?: ($names->get((string) $item->refugee_id) ?: '-'),
            $item->detail ?: '-',
            $item->actor ?: '-',
            $item->time ?: '-',
        ])->values();
    }

    protected function priorityRows(): Collection
    {
        $addresses = $this->mandiriAddresses();

        return $this->data->refugees()
            ->filter(fn (Record $item) => in_array($item->document_status, ['Perlu Verifikasi', 'Belum Lengkap'], true))
            ->map(fn (Record $item) => [
                $item->internal_id ?: '-',
                $item->name ?: '-',
                $item->nationality ?: '-',
                $this->locationLabel($item, $addresses),
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
