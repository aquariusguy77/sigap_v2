@extends('layouts.app')

@section('content')
    <section class="hero-panel">
        <div class="hero-copy">
            <span class="eyebrow"><x-icon name="shield" class="chip-icon" />Portal Login SIGAP</span>
            <h3>Selamat Datang di SIGAP Rudenim Surabaya.</h3>
            <p>Pendataan pengungsi luar negeri, dokumen, audit, dan pelaporan dalam satu sistem terpadu. Gunakan akun Laravel produksi atau sesi demo untuk pengujian peran (role) dengan cepat dan aman.</p>
        </div>
        <div class="hero-side">
            <div class="highlight-card">
                <div class="highlight-head">
                    <strong>Mode</strong>
                    <span class="mini-badge success">Role-ready</span>
                </div>
                <p>Pilih mode masuk yang sesuai. Jika akun Laravel belum tersedia, login demo tetap bisa dipakai untuk melihat pembatasan per role.</p>
            </div>
        </div>
    </section>

    <section class="panel" style="margin-top:24px;">
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="double-grid" style="margin-top:0;">
                <div>
                    <label class="table-meta">Mode Login</label>
                    <select class="control" name="login_mode" id="loginMode" required>
                        @foreach ($authModes as $key => $mode)
                            <option value="{{ $key }}" @selected(old('login_mode', $defaultAuthMode) === $key)>{{ $mode['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="demo-only-field">
                    <label class="table-meta">Nama</label>
                    <input class="control" type="text" name="name" id="demoName" value="{{ old('name', 'Supervisor Shift') }}">
                </div>
                <div>
                    <label class="table-meta">Email</label>
                    <input class="control" type="email" name="email" id="loginEmail" value="{{ old('email', 'sigap-demo@rudenim.local') }}">
                </div>
                <div class="auth-only-field">
                    <label class="table-meta">Password Laravel</label>
                    <input class="control" type="password" name="password" id="authPassword" placeholder="Isi saat memakai akun Laravel">
                </div>
                <div class="demo-only-field">
                    <label class="table-meta">Peran</label>
                    <select class="control" name="role" id="demoRole">
                        @foreach ($roles as $key => $role)
                            <option value="{{ $key }}" @selected(old('role', 'supervisor') === $key)>{{ $role['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="subtle-box demo-only-field" id="demoHelp">
                <h4>Petunjuk singkat</h4>
                <ul>
                    <li><strong>Login Demo</strong> memakai nama, email opsional, dan pilihan role.</li>
                    <li>Pakai mode ini saat review antarmuka, uji role, atau tahap handoff awal.</li>
                    <li>Role aktif ditentukan dari pilihan Admin, Petugas Pendataan, atau Supervisor.</li>
                </ul>
            </div>
            <div class="subtle-box auth-only-field" id="authHelp">
                <h4>Petunjuk akun Laravel</h4>
                <ul>
                    <li><strong>Akun Laravel</strong> memakai email dan password dari tabel <code>users</code>.</li>
                    <li>Role aktif dibaca dari kolom <code>role</code> pengguna yang berhasil login.</li>
                    <li>Mode ini cocok untuk integrasi internal saat auth project target sudah aktif.</li>
                </ul>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;">
                <button class="btn btn-primary" type="submit">
                    <x-icon name="shield" class="chip-icon" /> Masuk Sekarang
                </button>
                <a class="btn btn-ghost" href="{{ route('login') }}">Reset Form</a>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const modeSelect = document.getElementById('loginMode');
            const demoFields = document.querySelectorAll('.demo-only-field');
            const authFields = document.querySelectorAll('.auth-only-field');
            const demoName = document.getElementById('demoName');
            const demoRole = document.getElementById('demoRole');
            const authPassword = document.getElementById('authPassword');

            const applyMode = () => {
                const mode = modeSelect?.value || 'demo';
                const isDemo = mode === 'demo';

                demoFields.forEach((element) => {
                    element.style.display = isDemo ? '' : 'none';
                });

                authFields.forEach((element) => {
                    element.style.display = isDemo ? 'none' : '';
                });

                if (demoName) demoName.required = isDemo;
                if (demoRole) demoRole.required = isDemo;
                if (authPassword) authPassword.required = !isDemo;
            };

            modeSelect?.addEventListener('change', applyMode);
            applyMode();
        })();
    </script>
@endsection
