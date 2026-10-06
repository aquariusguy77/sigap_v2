@extends('layouts.app')

@section('content')
    <section class="double-grid">
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="shield" class="chip-icon" />Keamanan Akun</span>
                    <h3>Ganti kata sandi</h3>
                    <p class="section-intro">
                        Berlaku untuk akun <strong>{{ $currentUser['email'] }}</strong>.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('account.password.update') }}" autocomplete="off">
                @csrf
                @method('PUT')

                <div>
                    <label class="table-meta" for="currentPassword">Kata Sandi Saat Ini</label>
                    <div class="field-reveal">
                        <input class="control" type="password" name="current_password" id="currentPassword"
                               autocomplete="current-password" placeholder="Kata sandi yang Anda pakai sekarang" required>
                        <button type="button" class="field-eye" data-reveal="currentPassword"
                                aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <x-icon name="eye" />
                        </button>
                    </div>
                    @error('current_password')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
                </div>

                <div style="margin-top:16px;">
                    <label class="table-meta" for="newPassword">Kata Sandi Baru</label>
                    <div class="field-reveal">
                        <input class="control" type="password" name="password" id="newPassword"
                               autocomplete="new-password" placeholder="Minimal {{ $minimum }} karakter"
                               minlength="{{ $minimum }}" maxlength="72" required>
                        <button type="button" class="field-eye" data-reveal="newPassword"
                                aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <x-icon name="eye" />
                        </button>
                    </div>
                    @error('password')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
                </div>

                <div style="margin-top:16px;">
                    <label class="table-meta" for="confirmPassword">Ulangi Kata Sandi Baru</label>
                    <div class="field-reveal">
                        <input class="control" type="password" name="password_confirmation" id="confirmPassword"
                               autocomplete="new-password" placeholder="Ketik ulang kata sandi baru"
                               minlength="{{ $minimum }}" maxlength="72" required>
                        <button type="button" class="field-eye" data-reveal="confirmPassword"
                                aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <x-icon name="eye" />
                        </button>
                    </div>
                </div>

                <div style="display:flex;gap:9px;margin-top:20px;flex-wrap:wrap;">
                    <button class="btn btn-primary" type="submit">
                        <x-icon name="check" class="chip-icon" /> Simpan Kata Sandi
                    </button>
                    <a class="btn btn-ghost" href="{{ route('dashboard.index') }}">Batal</a>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="alert" class="chip-icon" />Catatan</span>
                    <h3>Memilih kata sandi</h3>
                    <p class="section-intro">Hal-hal yang berlaku pada sistem ini.</p>
                </div>
            </div>

            <div class="list-group">
                <article class="list-item">
                    <strong>Panjang lebih menentukan daripada campuran huruf</strong>
                    <p>
                        Minimal {{ $minimum }} karakter, maksimal 72. Tidak ada kewajiban
                        mencampur huruf besar, angka, dan tanda baca. Rangkaian beberapa
                        kata yang mudah Anda ingat umumnya lebih kuat daripada satu kata
                        pendek yang dipenuhi tanda baca.
                    </p>
                </article>
                <article class="list-item">
                    <strong>Kata sandi tidak dapat dikirim ulang</strong>
                    <p>
                        Sistem ini tidak mengirim surel, jadi tidak ada tautan "lupa kata
                        sandi". Bila kata sandi hilang, hanya Admin yang dapat mengaturnya
                        ulang dari halaman Hak Akses.
                    </p>
                </article>
                <article class="list-item">
                    <strong>Yang tersimpan hanya hasil pengacakannya</strong>
                    <p>
                        Kata sandi disimpan sebagai hash bcrypt, bukan teks aslinya. Tidak
                        seorang pun — termasuk Admin — dapat membaca kembali kata sandi
                        yang sudah Anda simpan.
                    </p>
                </article>
                <article class="list-item">
                    <strong>Percobaan masuk dibatasi</strong>
                    <p>
                        Lima kali gagal dari satu perangkat akan mengunci percobaan masuk
                        selama 15 menit. Jadi pastikan kata sandi baru tercatat di tempat
                        yang aman sebelum Anda keluar dari sistem.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <script>
        document.querySelectorAll('.field-eye').forEach((tombol) => {
            tombol.addEventListener('click', () => {
                const kolom = document.getElementById(tombol.dataset.reveal);
                if (!kolom) return;

                const tersembunyi = kolom.type === 'password';
                kolom.type = tersembunyi ? 'text' : 'password';
                tombol.setAttribute('aria-pressed', String(tersembunyi));
                tombol.setAttribute('aria-label', tersembunyi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                kolom.focus();
            });
        });
    </script>
@endsection
