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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Akun milik pengguna yang sedang masuk.
 *
 * Sebelum ada halaman ini, kata sandi hanya dapat diganti dengan menimpa node
 * users di Firebase secara manual. Akibatnya kata sandi awal yang dibagikan
 * lewat perintah seed cenderung dipakai terus-menerus.
 */
class AccountController extends Controller
{
    public function __construct(
        protected RoleAccessService $roleAccessService,
        protected UserRepository $users,
        protected LoginThrottleService $loginThrottle
    ) {
    }

    public function editPassword(): View
    {
        $this->ensureAbility('manage-settings');

        return view('account.password', array_merge($this->baseViewData(), [
            'pageHeading' => 'Ganti Kata Sandi',
            'pageDescription' => 'Mengubah kata sandi akun Anda sendiri.',
            'minimum' => $this->minimum(),
        ]));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $this->ensureAbility('manage-settings');

        $minimum = $this->minimum();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],

            /*
             * bcrypt hanya memperhitungkan 72 bita pertama. Tanpa batas atas,
             * kata sandi yang lebih panjang akan terpotong diam-diam dan
             * pengguna tidak akan pernah tahu bagian mana yang sebenarnya
             * dipakai.
             */
            'password' => ['required', 'string', 'min:' . $minimum, 'max:72', 'confirmed'],
        ], [
            'password.min' => 'Kata sandi baru minimal ' . $minimum . ' karakter.',
            'password.max' => 'Kata sandi baru maksimal 72 karakter.',
            'password.confirmed' => 'Ulangi kata sandi baru dengan isi yang sama.',
        ], [
            'current_password' => 'kata sandi saat ini',
            'password' => 'kata sandi baru',
        ]);

        $pengguna = Auth::user();

        if (! $pengguna) {
            return redirect()->route('login');
        }

        if (! Hash::check($validated['current_password'], $pengguna->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => 'Kata sandi saat ini tidak cocok.',
            ]);
        }

        if (Hash::check($validated['password'], $pengguna->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            ]);
        }

        try {
            $this->users->update((string) $pengguna->getAuthIdentifier(), [
                'password' => Hash::make($validated['password']),
                'password_changed_at' => now()->toIso8601String(),
            ]);
        } catch (FirebaseWriteException $e) {
            /*
             * Kegagalan menulis tidak boleh dilaporkan sebagai keberhasilan.
             * Kata sandi lama masih berlaku bila baris ini tercapai.
             */
            return back()->withErrors([
                'password' => 'Kata sandi gagal disimpan ke Firebase, jadi kata sandi lama masih berlaku. ' . $e->getMessage(),
            ]);
        }

        /*
         * Catatan percobaan gagal milik akun ini dibersihkan, supaya kegagalan
         * sebelum penggantian tidak ikut membatasi percobaan sesudahnya.
         */
        $this->loginThrottle->clear((string) $pengguna->email, $request->ip());

        /*
         * Identitas sesi diperbarui agar kuki sesi yang mungkin sudah tersalin
         * di tempat lain tidak lagi dapat dipakai.
         */
        $request->session()->regenerate();

        return redirect()
            ->route('account.password.edit')
            ->with('status', 'Kata sandi berhasil diganti. Gunakan kata sandi baru pada masuk berikutnya.');
    }

    protected function minimum(): int
    {
        return max(8, (int) config('sigap.password.minimum', 12));
    }
}
