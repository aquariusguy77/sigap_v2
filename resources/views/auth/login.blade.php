@extends('layouts.auth')

@section('content')
<div class="auth-stage">
    {{-- Lambang besar berkadar rendah sebagai latar. --}}
    <img class="auth-watermark" src="{{ config('branding.logo_url') . '?v=' . config('branding.asset_version') }}" alt="" aria-hidden="true">

    {{-- Lengkung emas dan tosca tipis, digambar sebaris agar selalu termuat. --}}
    <svg class="auth-arcs" viewBox="0 0 900 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <g fill="none" stroke="#c8951a" stroke-opacity=".2">
            <circle cx="80" cy="630" r="210" stroke-width="1.1"/>
            <circle cx="80" cy="630" r="300" stroke-width="1.1"/>
        </g>
        <g fill="none" stroke="#7fd3e6" stroke-opacity=".15">
            <circle cx="830" cy="70" r="180" stroke-width="1.1"/>
            <circle cx="830" cy="70" r="264" stroke-width="1.1"/>
        </g>
    </svg>

    <div class="auth-card">
        <div class="auth-head">
            <span class="auth-logo">
                <img src="{{ config('branding.logo_url') . '?v=' . config('branding.asset_version') }}" alt="{{ config('branding.logo_alt') }}">
            </span>
            <strong>SIGAP</strong>
            <span class="auth-institution">Rumah Detensi Imigrasi Surabaya</span>
            <h1>Masuk ke sistem</h1>
            <p>Gunakan akun petugas yang terdaftar.</p>
        </div>

        @if ($errors->any())
            <div class="auth-alert" role="alert">
                <x-icon name="alert" />
                <div>
                    <strong>Belum bisa masuk</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @elseif (session('status'))
            <div class="auth-note" role="status">
                <x-icon name="alert" />
                <div>{{ session('status') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="auth-field">
                <label for="loginEmail">Email</label>
                <input class="control" type="email" name="email" id="loginEmail"
                       value="{{ old('email') }}" autocomplete="username"
                       placeholder="nama@sigap-rudenim.local" required autofocus>
            </div>

            <div class="auth-field">
                <label for="authPassword">Kata Sandi</label>
                <div class="auth-password">
                    <input class="control" type="password" name="password" id="authPassword"
                           autocomplete="current-password" placeholder="Masukkan kata sandi" required>
                    <button type="button" class="auth-eye" id="togglePassword"
                            aria-label="Tampilkan kata sandi" aria-pressed="false">
                        <x-icon name="eye" />
                    </button>
                </div>
            </div>

            <button class="btn btn-gold auth-submit" type="submit">
                <x-icon name="shield" class="chip-icon" /> Masuk
            </button>
        </form>

        <p class="auth-help">
            Lupa kata sandi atau belum punya akun? Hubungi Admin sistem —
            akun hanya dapat dibuat oleh petugas yang berwenang.
        </p>
    </div>

    <p class="auth-foot">
        Data pada sistem ini bersifat terbatas. Gunakan akun yang diberikan kepada Anda,
        dan jangan membagikannya kepada siapa pun.
    </p>
</div>

<script>
    (() => {
        const tombol = document.getElementById('togglePassword');
        const kolom = document.getElementById('authPassword');

        tombol?.addEventListener('click', () => {
            const tersembunyi = kolom.type === 'password';
            kolom.type = tersembunyi ? 'text' : 'password';
            tombol.setAttribute('aria-pressed', String(tersembunyi));
            tombol.setAttribute('aria-label', tersembunyi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            kolom.focus();
        });
    })();
</script>
@endsection
