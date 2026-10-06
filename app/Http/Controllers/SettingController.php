<?php

namespace App\Http\Controllers;

use App\Exceptions\FirebaseWriteException;
use App\Firebase\UserRepository;
use App\Services\LoginThrottleService;
use App\Services\RoleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        protected RoleAccessService $roleAccessService,
        protected UserRepository $users,
        protected LoginThrottleService $loginThrottle
    ) {
    }

    public function index(): View
    {
        $this->ensureAbility('manage-settings');

        return view('settings.index', array_merge($this->baseViewData(), [
            'pageHeading' => 'Hak Akses',
            'pageDescription' => 'Akun terdaftar beserta kewenangan tiap peran.',
            'roles' => $this->roleAccessService->roles(),
            'roleFlow' => $this->roleAccessService->flow(),
            'accounts' => $this->users->listing(),
            'minimum' => $this->minimum(),
        ]));
    }

    /**
     * Mengatur ulang kata sandi akun lain.
     *
     * Diperlukan karena sistem ini tidak mengirim surel: tidak ada tautan
     * "lupa kata sandi" yang dapat dikirim ke petugas. Halaman masuk pun
     * mengarahkan petugas yang lupa kata sandinya untuk menghubungi Admin,
     * jadi Admin harus benar-benar punya cara menolong.
     */
    public function resetPassword(Request $request, string $user): RedirectResponse
    {
        $this->ensureAbility('manage-settings');

        $minimum = $this->minimum();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:' . $minimum, 'max:72', 'confirmed'],
        ], [
            'password.min' => 'Kata sandi baru minimal ' . $minimum . ' karakter.',
            'password.max' => 'Kata sandi baru maksimal 72 karakter.',
            'password.confirmed' => 'Ulangi kata sandi baru dengan isi yang sama.',
        ], [
            'password' => 'kata sandi baru',
        ]);

        $akun = $this->users->find($user);

        if (! $akun) {
            return back()->withErrors(['password' => 'Akun yang dituju tidak ditemukan.']);
        }

        try {
            $this->users->update((string) $akun->id, [
                'password' => Hash::make($validated['password']),
                'password_changed_at' => now()->toIso8601String(),
            ]);
        } catch (FirebaseWriteException $e) {
            return back()->withErrors([
                'password' => 'Kata sandi gagal disimpan ke Firebase, jadi kata sandi lama masih berlaku. ' . $e->getMessage(),
            ]);
        }

        /*
         * Penguncian akibat percobaan gagal ikut dilepas. Akun yang terkunci
         * justru keadaan yang paling sering membuat petugas minta tolong, jadi
         * mengatur ulang kata sandi tanpa melepas kuncinya tidak menyelesaikan
         * masalahnya.
         */
        $this->loginThrottle->clear((string) $akun->email, null);
        $this->loginThrottle->clear((string) $akun->email, $request->ip());

        /*
         * Bila Admin mengatur ulang kata sandinya sendiri, sesinya diperbarui
         * supaya tetap masuk tanpa perlu memasukkan kata sandi baru dua kali.
         */
        if (Auth::check() && (string) Auth::user()->getAuthIdentifier() === (string) $akun->id) {
            $request->session()->regenerate();
        }

        return redirect()
            ->route('settings.index')
            ->with('status', 'Kata sandi ' . $akun->email . ' berhasil diatur ulang. Sampaikan kata sandi baru itu langsung kepada pemiliknya, dan minta ia menggantinya sendiri setelah masuk.');
    }

    protected function minimum(): int
    {
        return max(8, (int) config('sigap.password.minimum', 12));
    }
}
