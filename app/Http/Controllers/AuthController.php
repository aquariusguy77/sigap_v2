<?php

namespace App\Http\Controllers;

use App\Services\LoginThrottleService;
use App\Services\RoleAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected RoleAccessService $roleAccessService,
        protected LoginThrottleService $loginThrottle
    ) {
    }

    public function showLogin(): View|RedirectResponse
    {
        if ($this->roleAccessService->isSignedIn()) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.login');
    }

    /**
     * Memeriksa akun dan memulai sesi.
     *
     * Mode demo yang dulu ada di sini sudah dihapus. Mode itu memberi akses
     * penuh hanya bermodal nama dan pilihan peran, tanpa kata sandi sama
     * sekali, sehingga siapa pun yang membuka alamat aplikasi dapat masuk
     * sebagai Admin lalu menghapus data. Satu-satunya jalan masuk sekarang
     * adalah akun terdaftar yang kata sandinya diperiksa terhadap hash bcrypt
     * di Firebase.
     *
     * Jumlah percobaan dibatasi oleh LoginThrottleService, yang menyimpan
     * hitungannya di Firebase. Tanpa itu halaman ini dapat dipakai menebak
     * kata sandi tanpa batas.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string'],
        ], [], [
            'email' => 'email',
            'password' => 'kata sandi',
        ]);

        $alamat = $request->ip();

        /*
         * Diperiksa sebelum kata sandi diuji, supaya percobaan yang sedang
         * terkunci tidak menambah beban pembacaan akun ke Firebase.
         */
        $terkunci = $this->loginThrottle->lockedFor($validated['email'], $alamat);

        if ($terkunci > 0) {
            return back()
                ->withErrors(['email' => $this->loginThrottle->message($terkunci)])
                ->withInput($request->except('password'));
        }

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            $this->loginThrottle->recordFailure($validated['email'], $alamat);

            /*
             * Sengaja tidak membedakan "email tidak terdaftar" dari "kata sandi
             * salah", agar halaman ini tidak dapat dipakai menebak email mana
             * yang punya akun.
             */
            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])
                ->withInput($request->except('password'));
        }

        $this->loginThrottle->clear($validated['email'], $alamat);

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard.index')
            ->with('status', 'Berhasil masuk. Peran aktif mengikuti akun Anda.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Anda sudah keluar dari sistem.');
    }
}
