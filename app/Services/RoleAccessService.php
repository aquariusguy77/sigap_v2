<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

/**
 * Menentukan peran pengguna aktif dan kewenangannya.
 *
 * Peran diambil dari akun Firebase yang sedang login. Mode demo yang dulu
 * memungkinkan masuk tanpa kata sandi sudah dihapus, sehingga tidak ada lagi
 * peran yang berasal dari sesi tanpa akun.
 */
class RoleAccessService
{
    public function roles(): array
    {
        return [
            'admin' => [
                'label' => 'Admin',
                'abilities' => ['full-access', 'manage-refugees', 'manage-documents', 'manage-placements', 'manage-reports', 'view-reports', 'manage-settings', 'review-changes'],
            ],
            'petugas' => [
                'label' => 'Petugas Pendataan',
                'abilities' => ['manage-refugees', 'manage-documents', 'manage-placements'],
            ],
            'supervisor' => [
                'label' => 'Supervisor',
                'abilities' => ['review-changes', 'verify-documents', 'view-reports'],
            ],
        ];
    }

    public function flow(): array
    {
        return [
            ['step' => 'Input awal', 'actor' => 'Petugas Pendataan', 'description' => 'Mengisi identitas, penempatan, dan mengunggah dokumen awal.'],
            ['step' => 'Pemeriksaan', 'actor' => 'Supervisor', 'description' => 'Memeriksa perubahan penting, memverifikasi dokumen, dan meninjau mutasi.'],
            ['step' => 'Finalisasi', 'actor' => 'Admin', 'description' => 'Mengelola akun, ekspor laporan, dan penghapusan data sensitif.'],
        ];
    }

    public function currentRoleKey(): string
    {
        $role = Auth::check() ? (string) (Auth::user()->role ?? '') : '';

        /*
         * Peran yang tidak dikenal jatuh ke supervisor, peran dengan kewenangan
         * paling sempit. Lebih baik petugas kekurangan akses lalu melapor,
         * daripada kelebihan akses tanpa ada yang menyadari.
         */
        return array_key_exists($role, $this->roles()) ? $role : 'supervisor';
    }

    public function currentRole(): array
    {
        if (! $this->isSignedIn()) {
            return ['key' => 'guest', 'label' => 'Belum Login', 'abilities' => [], 'source' => 'guest'];
        }

        $key = $this->currentRoleKey();

        return [
            'key' => $key,
            'label' => $this->roles()[$key]['label'],
            'abilities' => $this->roles()[$key]['abilities'],
            'source' => 'auth',
        ];
    }

    /**
     * Satu-satunya penentu apakah seseorang sudah masuk.
     *
     * Dulu metode ini juga menganggap masuk bila sesi menyimpan peran demo,
     * atau bila variabel SIGAP_ACTIVE_ROLE diisi di lingkungan. Keduanya
     * dihapus: yang pertama membuka akses tanpa kata sandi, yang kedua membuat
     * setiap pengunjung otomatis berperan begitu variabelnya salah diisi.
     */
    public function isSignedIn(): bool
    {
        return Auth::check();
    }

    public function currentUser(): array
    {
        if (Auth::check()) {
            return [
                'name' => (string) (Auth::user()->name ?? 'Pengguna'),
                'email' => (string) (Auth::user()->email ?? ''),
                'role' => $this->currentRole(),
            ];
        }

        return ['name' => 'Tamu', 'email' => '', 'role' => $this->currentRole()];
    }

    public function can(string $ability, ?string $role = null): bool
    {
        if ($role === null && ! $this->isSignedIn()) {
            return false;
        }

        $abilities = $this->roles()[$role ?: $this->currentRoleKey()]['abilities'] ?? [];

        return in_array('full-access', $abilities, true) || in_array($ability, $abilities, true);
    }
}
