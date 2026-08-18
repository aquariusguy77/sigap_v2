<?php

namespace App\Console\Commands;

use App\Firebase\AuditTrailRepository;
use App\Firebase\DocumentRepository;
use App\Firebase\PlacementRepository;
use App\Firebase\RefugeeRepository;
use App\Firebase\UserRepository;
use App\Services\GoogleTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Mengisi Firebase dengan akun awal dan data contoh.
 *
 * Menggantikan peran seeder database, karena aplikasi ini tidak lagi memakai
 * database relasional.
 */
class SeedFirebase extends Command
{
    protected $signature = 'sigap:seed
                            {--akun-saja : Hanya membuat akun pengguna, tanpa data contoh}
                            {--data-saja : Hanya membuat data contoh, tanpa akun}';

    protected $description = 'Mengisi Firebase dengan akun awal dan data contoh SIGAP';

    public function handle(
        GoogleTokenService $tokens,
        UserRepository $users,
        RefugeeRepository $refugees,
        PlacementRepository $placements,
        DocumentRepository $documents,
        AuditTrailRepository $audits
    ): int {
        $this->info('Menyiapkan data awal SIGAP di Firebase...');
        $this->newLine();

        if (blank(config('sigap.firebase.database_url'))) {
            $this->error('FIREBASE_DATABASE_URL belum diisi pada berkas .env.');

            return self::FAILURE;
        }

        if (! $tokens->hasServiceAccount() && blank(config('sigap.firebase.database_secret'))) {
            $this->warn('Kredensial belum diisi. Pengisian hanya berhasil bila aturan');
            $this->warn('keamanan Realtime Database mengizinkan penulisan publik.');
            $this->newLine();
        }

        try {
            if (! $this->option('data-saja')) {
                $this->seedUsers($users);
            }

            if (! $this->option('akun-saja')) {
                $this->seedContent($refugees, $placements, $documents, $audits);
            }
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('Gagal menulis ke Firebase: ' . $e->getMessage());
            $this->line('Periksa FIREBASE_DATABASE_URL, kredensial, dan aturan keamanan.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Selesai. Silakan buka aplikasi dan coba masuk.');

        return self::SUCCESS;
    }

    protected function seedUsers(UserRepository $users): void
    {
        $this->line('Membuat akun pengguna:');

        $accounts = [
            ['name' => 'Administrator SIGAP', 'email' => 'admin@sigap-rudenim.local', 'password' => 'Sigap@Admin2026', 'role' => 'admin'],
            ['name' => 'Petugas Pendataan', 'email' => 'petugas@sigap-rudenim.local', 'password' => 'Sigap@Petugas2026', 'role' => 'petugas'],
            ['name' => 'Supervisor Shift', 'email' => 'supervisor@sigap-rudenim.local', 'password' => 'Sigap@Supervisor2026', 'role' => 'supervisor'],
        ];

        foreach ($accounts as $account) {
            $existing = $users->findByEmail($account['email']);

            $payload = [
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => Hash::make($account['password']),
                'role' => $account['role'],
                'status' => 'Aktif',
            ];

            if ($existing) {
                $users->update((string) $existing->id, $payload);
                $this->line('  diperbarui  ' . $account['email']);
            } else {
                $users->create($payload);
                $this->line('  dibuat      ' . $account['email'] . '  (kata sandi: ' . $account['password'] . ')');
            }
        }
    }

    protected function seedContent(
        RefugeeRepository $refugees,
        PlacementRepository $placements,
        DocumentRepository $documents,
        AuditTrailRepository $audits
    ): void {
        if ($refugees->all()->isNotEmpty()) {
            $this->newLine();
            $this->line('Data pengungsi sudah ada, pengisian data contoh dilewati.');

            return;
        }

        $this->newLine();
        $this->line('Membuat data contoh (seluruhnya data sintetis):');

        $samples = [
            ['internal_id' => 'RDS-24001', 'name' => 'Amina Hassan', 'nationality' => 'Somalia', 'unhcr_number' => 'UNHCR-SOM-8812', 'status' => 'Aktif', 'location' => 'Hunian A-03', 'document_status' => 'Lengkap', 'notes' => 'Dokumen keluarga lengkap.'],
            ['internal_id' => 'RDS-24008', 'name' => 'Mahmoud Kareem', 'nationality' => 'Irak', 'unhcr_number' => 'UNHCR-IRQ-4471', 'status' => 'Verifikasi', 'location' => 'Hunian B-02', 'document_status' => 'Perlu Verifikasi', 'notes' => 'Menunggu pemeriksaan supervisor.'],
            ['internal_id' => 'RDS-24011', 'name' => 'Samira Nabil', 'nationality' => 'Afghanistan', 'unhcr_number' => 'UNHCR-AFG-2290', 'status' => 'Aktif', 'location' => 'Hunian C-05', 'document_status' => 'Lengkap', 'notes' => 'Kondisi stabil.'],
            ['internal_id' => 'RDS-24016', 'name' => 'Yousef Rahman', 'nationality' => 'Myanmar', 'unhcr_number' => 'UNHCR-MMR-6654', 'status' => 'Mutasi', 'location' => 'Transit 1', 'document_status' => 'Belum Lengkap', 'notes' => 'Dalam proses perpindahan.'],
            ['internal_id' => 'RDS-24021', 'name' => 'Layla Aziz', 'nationality' => 'Sudan', 'unhcr_number' => 'UNHCR-SDN-1180', 'status' => 'Aktif', 'location' => 'Hunian A-01', 'document_status' => 'Belum Lengkap', 'notes' => 'Berkas identitas belum lengkap.'],
            ['internal_id' => 'RDS-24027', 'name' => 'Karim Saeed', 'nationality' => 'Yaman', 'unhcr_number' => 'UNHCR-YEM-7130', 'status' => 'Verifikasi', 'location' => 'Hunian D-02', 'document_status' => 'Perlu Verifikasi', 'notes' => 'Perlu surat pendukung tambahan.'],
        ];

        $created = [];

        foreach ($samples as $index => $sample) {
            $sample['registered_at'] = now()->subDays(30 - $index * 3)->toIso8601String();
            $record = $refugees->create($sample);
            $created[] = $record;
            $this->line('  pengungsi   ' . $sample['name']);
        }

        foreach ($created as $index => $refugee) {
            $placements->create([
                'refugee_id' => $refugee->id,
                'refugee_name' => $refugee->name,
                'location_name' => $refugee->location,
                'entered_at' => now()->subDays(28 - $index * 3)->toDateString(),
                'exited_at' => null,
                'placement_status' => $refugee->status === 'Mutasi' ? 'Mutasi' : 'Aktif',
                'notes' => 'Pencatatan awal penempatan.',
            ]);
        }

        $this->line('  penempatan  ' . count($created) . ' catatan');

        $types = ['Identitas Utama', 'Administrasi Internal', 'Riwayat Penempatan', 'Lampiran Tambahan'];

        foreach ($created as $index => $refugee) {
            $documents->create([
                'refugee_id' => $refugee->id,
                'refugee_name' => $refugee->name,
                'document_type' => $types[$index % count($types)],
                'file_name' => 'contoh-' . strtolower(str_replace(' ', '-', $refugee->name)) . '.pdf',
                'file_path' => null,
                'download_url' => null,
                'verification_status' => $refugee->document_status,
                'uploaded_at' => now()->subDays(20 - $index)->toIso8601String(),
                'uploaded_by' => 'Petugas Pendataan',
                'notes' => 'Berkas contoh untuk pengujian.',
            ]);
        }

        $this->line('  dokumen     ' . count($created) . ' berkas');

        foreach ($created as $index => $refugee) {
            $audits->record([
                'refugee_id' => $refugee->id,
                'field_name' => 'Data pengungsi',
                'new_value' => $refugee->internal_id,
                'action_label' => 'Data pengungsi ditambahkan',
                'performed_by_name' => 'Petugas Pendataan',
                'reason' => 'Pengisian data awal',
                'performed_at' => now()->subDays(30 - $index * 3)->toIso8601String(),
            ]);
        }

        $this->line('  riwayat     ' . count($created) . ' catatan');
    }
}
