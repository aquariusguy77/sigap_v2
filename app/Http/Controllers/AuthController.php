<?php

namespace App\Http\Controllers;

use App\Services\RoleAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected RoleAccessService $roleAccessService
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

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            /*
             * Sengaja tidak membedakan "email tidak terdaftar" dari "kata sandi
             * salah", agar halaman ini tidak dapat dipakai menebak email mana
             * yang punya akun.
             */
            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])
                ->withInput($request->except('password'));
        }

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
