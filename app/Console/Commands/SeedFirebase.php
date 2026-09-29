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

    /**
     * Membuat tiga akun awal.
     *
     * Kata sandinya sengaja tidak lagi ditulis di dalam kode. Berkas ini
     * berada di repositori publik, sehingga kata sandi yang tertulis di sini
     * sama saja dengan mengumumkannya — siapa pun yang menemukan repositorinya
     * dapat langsung masuk ke aplikasi yang tayang.
     *
     * Urutan pengambilannya:
     *   1. Variabel lingkungan, misalnya SIGAP_SEED_ADMIN_PASSWORD
     *   2. Bila kosong, dibuatkan kata sandi acak yang ditampilkan SEKALI di
     *      layar. Catat saat itu juga; nilainya tidak tersimpan di mana pun
     *      selain sebagai hash di Firebase.
     */
    protected function seedUsers(UserRepository $users): void
    {
        $this->line('Membuat akun pengguna:');

        $accounts = [
            ['name' => 'Administrator SIGAP', 'email' => 'admin@sigap-rudenim.local', 'role' => 'admin', 'env' => 'SIGAP_SEED_ADMIN_PASSWORD'],
            ['name' => 'Petugas Pendataan', 'email' => 'petugas@sigap-rudenim.local', 'role' => 'petugas', 'env' => 'SIGAP_SEED_PETUGAS_PASSWORD'],
            ['name' => 'Supervisor Shift', 'email' => 'supervisor@sigap-rudenim.local', 'role' => 'supervisor', 'env' => 'SIGAP_SEED_SUPERVISOR_PASSWORD'],
        ];

        $acak = [];

        foreach ($accounts as $account) {
            $existing = $users->findByEmail($account['email']);

            /*
             * Akun yang sudah ada tidak diganggu kata sandinya. Menjalankan
             * ulang perintah ini tidak boleh diam-diam mengganti kata sandi
             * yang sudah dipakai petugas.
             */
            if ($existing) {
                $users->update((string) $existing->id, [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'role' => $account['role'],
                    'status' => 'Aktif',
                ]);
                $this->line('  dipertahankan  ' . $account['email'] . '  (kata sandi tidak diubah)');

                continue;
            }

            $sandi = (string) env($account['env'], '');
            $dariEnv = $sandi !== '';

            if (! $dariEnv) {
                $sandi = $this->buatKataSandi();
                $acak[$account['email']] = $sandi;
            }

            $users->create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => Hash::make($sandi),
                'role' => $account['role'],
                'status' => 'Aktif',
            ]);

            $this->line('  dibuat         ' . $account['email'] . ($dariEnv ? '  (kata sandi dari ' . $account['env'] . ')' : ''));
        }

        if ($acak !== []) {
            $this->newLine();
            $this->warn('  Kata sandi berikut hanya ditampilkan sekali. Catat sekarang juga:');
            foreach ($acak as $email => $sandi) {
                $this->line('    ' . str_pad($email, 34) . $sandi);
            }
            $this->line('  Untuk menentukan sendiri, isi SIGAP_SEED_ADMIN_PASSWORD dan kawan-kawannya di .env.');
        }
    }

    /**
     * Kata sandi acak yang cukup panjang dan tetap mudah disalin.
     */
    protected function buatKataSandi(): string
    {
        $huruf = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $angka = '23456789';
        $tanda = '!@#$%&*?';

        $isi = [
            $huruf[random_int(0, strlen($huruf) - 1)],
            $angka[random_int(0, strlen($angka) - 1)],
            $tanda[random_int(0, strlen($tanda) - 1)],
        ];

        $semua = $huruf . $angka . $tanda;

        for ($i = 0; $i < 13; $i++) {
            $isi[] = $semua[random_int(0, strlen($semua) - 1)];
        }

        shuffle($isi);

        return implode('', $isi);
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

        /*
         * Tiga pengungsi berfasilitas IOM dan tiga mandiri, supaya kedua
         * kategori penempatan langsung terlihat begitu aplikasi dibuka.
         * Alamat dan koordinat di bawah ini sepenuhnya sintetis.
         */
        $samples = [
            ['internal_id' => 'RDS-24001', 'name' => 'Amina Hassan', 'nationality' => 'Somalia', 'unhcr_number' => 'UNHCR-SOM-8812', 'phone' => '0812-3311-4501', 'status' => 'Aktif', 'location' => 'CH Puspa Agro', 'document_status' => 'Lengkap', 'notes' => 'Kedua kartu sudah dikumpulkan.',
                'placement' => ['category' => 'iom', 'community_house' => 'CH Puspa Agro']],
            ['internal_id' => 'RDS-24008', 'name' => 'Mahmoud Kareem', 'nationality' => 'Irak', 'unhcr_number' => 'UNHCR-IRQ-4471', 'phone' => '0813-5522-8830', 'status' => 'Perlu Verifikasi', 'location' => 'CH Green Bamboo', 'document_status' => 'Perlu Verifikasi', 'notes' => 'Menunggu pemeriksaan supervisor.',
                'placement' => ['category' => 'iom', 'community_house' => 'CH Green Bamboo']],
            ['internal_id' => 'RDS-24011', 'name' => 'Samira Nabil', 'nationality' => 'Afghanistan', 'unhcr_number' => 'UNHCR-AFG-2290', 'phone' => '0857-7788-1204', 'status' => 'Aktif', 'location' => 'CH Puspa Agro', 'document_status' => 'Lengkap', 'notes' => 'Kondisi stabil.',
                'placement' => ['category' => 'iom', 'community_house' => 'CH Puspa Agro']],
            ['internal_id' => 'RDS-24016', 'name' => 'Yousef Rahman', 'nationality' => 'Myanmar', 'unhcr_number' => 'UNHCR-MMR-6654', 'phone' => '0821-4499-6612', 'status' => 'Perlu Verifikasi', 'location' => 'Pengungsi Mandiri', 'document_status' => 'Belum Lengkap', 'notes' => 'Dalam proses perpindahan.',
                'placement' => ['category' => 'mandiri', 'address' => 'Jalan Jemur Andayani III No. 24, RT 03 RW 05, Kelurahan Jemurwonosari, Kecamatan Wonocolo, Surabaya', 'latitude' => '-7.328912', 'longitude' => '112.734501']],
            ['internal_id' => 'RDS-24021', 'name' => 'Layla Aziz', 'nationality' => 'Sudan', 'unhcr_number' => 'UNHCR-SDN-1180', 'phone' => '0878-2200-7745', 'status' => 'Aktif', 'location' => 'Pengungsi Mandiri', 'document_status' => 'Belum Lengkap', 'notes' => 'Kartu Wajib Lapor belum dikumpulkan.',
                'placement' => ['category' => 'mandiri', 'address' => 'Jalan Rungkut Asri Tengah XI No. 7, RT 01 RW 09, Kelurahan Rungkut Kidul, Kecamatan Rungkut, Surabaya', 'latitude' => '-7.334215', 'longitude' => '112.775338']],
            ['internal_id' => 'RDS-24027', 'name' => 'Karim Saeed', 'nationality' => 'Yaman', 'unhcr_number' => 'UNHCR-YEM-7130', 'phone' => '0896-6611-3390', 'status' => 'Perlu Verifikasi', 'location' => 'Pengungsi Mandiri', 'document_status' => 'Perlu Verifikasi', 'notes' => 'Alamat sudah diverifikasi petugas lapangan.',
                'placement' => ['category' => 'mandiri', 'address' => 'Jalan Keputih Tegal Timur No. 118, RT 02 RW 04, Kelurahan Keputih, Kecamatan Sukolilo, Surabaya']],
        ];

        $created = [];

        $placementPlans = [];

        foreach ($samples as $index => $sample) {
            $placementPlans[] = $sample['placement'];
            unset($sample['placement']);

            $sample['registered_at'] = now()->subDays(30 - $index * 3)->toIso8601String();
            $record = $refugees->create($sample);
            $created[] = $record;
            $this->line('  pengungsi   ' . $sample['name']);
        }

        foreach ($created as $index => $refugee) {
            $placements->create(array_merge($placementPlans[$index], [
                'refugee_id' => $refugee->id,
                'refugee_name' => $refugee->name,
                'entered_at' => now()->subDays(28 - $index * 3)->toDateString(),
                'exited_at' => null,
                // Ikut status pengungsinya agar kedua nilai sama-sama terlihat.
                'placement_status' => $refugee->status === 'Perlu Verifikasi' ? 'Perlu Verifikasi' : 'Aktif',
                'notes' => $placementPlans[$index]['category'] === 'mandiri'
                    ? 'Tempat tinggal dicari sendiri oleh pengungsi.'
                    : 'Ditempatkan di Community House oleh IOM.',
            ]));
        }

        $this->line('  penempatan  ' . count($created) . ' catatan');

        // Hanya dua berkas yang wajib dikumpulkan pengungsi.
        $types = config('sigap.reference.document_types', ['Kartu Pengungsi', 'Kartu Wajib Lapor']);

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
