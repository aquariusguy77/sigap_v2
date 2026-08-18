<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

/**
 * Menentukan peran pengguna aktif dan kewenangannya.
 *
 * Peran diambil dari akun Firebase yang sedang login, atau dari sesi demo bila
 * mode demo diaktifkan.
 */
class RoleAccessService
{
    public function authModes(): array
    {
        $modes = [];

        if ((bool) config('sigap.auth.demo_enabled', true)) {
            $modes['demo'] = [
                'label' => 'Login Demo',
                'description' => 'Masuk cepat dengan peran simulasi, tanpa akun.',
            ];
        }

        if ((bool) config('sigap.auth.laravel_auth_enabled', true)) {
            $modes['auth'] = [
                'label' => 'Akun Terdaftar',
                'description' => 'Masuk dengan email dan kata sandi dari Firebase.',
            ];
        }

        return $modes;
    }

    public function defaultAuthMode(): string
    {
        $configured = (string) config('sigap.auth.login_mode', 'hybrid');
        $modes = $this->authModes();

        if ($configured === 'auth' && array_key_exists('auth', $modes)) {
            return 'auth';
        }

        if ($configured === 'demo' && array_key_exists('demo', $modes)) {
            return 'demo';
        }

        return array_key_first($modes) ?? 'demo';
    }

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
        $authRole = Auth::check() ? (string) (Auth::user()->role ?? '') : '';
        $sessionRole = (string) session('sigap_user.role', '');
        $envRole = (string) config('sigap.auth.active_role_fallback', '');

        $role = $authRole !== '' ? $authRole : ($sessionRole !== '' ? $sessionRole : $envRole);

        return array_key_exists($role, $this->roles()) ? $role : 'supervisor';
    }

    public function currentRole(): array
    {
        if (! $this->isSignedIn()) {
            return ['key' => 'guest', 'label' => 'Belum Login', 'abilities' => [], 'source' => 'guest'];
        }

        $key = $this->currentRoleKey();
        $source = Auth::check() && filled(Auth::user()->role ?? null)
            ? 'auth'
            : (filled(session('sigap_user.role')) ? 'session' : 'env');

        return [
            'key' => $key,
            'label' => $this->roles()[$key]['label'],
            'abilities' => $this->roles()[$key]['abilities'],
            'source' => $source,
        ];
    }

    public function isSignedIn(): bool
    {
        return Auth::check()
            || filled(session('sigap_user.role'))
            || array_key_exists((string) config('sigap.auth.active_role_fallback', ''), $this->roles());
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

        if (filled(session('sigap_user.name'))) {
            return [
                'name' => (string) session('sigap_user.name'),
                'email' => (string) session('sigap_user.email', ''),
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
