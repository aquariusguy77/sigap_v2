@extends('layouts.app')

@section('content')
    <section class="panel section-anchor" id="hak-akses">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="users" class="chip-icon" />Akun Pengguna</span>
                <h3>Daftar akun terdaftar</h3>
                <p class="section-intro">Akun tersimpan di Firebase. Kata sandi disimpan dalam bentuk hash dan tidak pernah ditampilkan.</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span class="badge">Peran aktif: {{ $currentRole['label'] }}</span>
                <span class="badge">{{ $accounts->count() }} akun</span>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Status</th>
                        <th>Kata Sandi Diubah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td><span class="cell-title">{{ $account->name }}</span></td>
                            <td class="table-meta">{{ $account->email }}</td>
                            <td><span class="badge">{{ $roles[$account->role]['label'] ?? $account->role }}</span></td>
                            <td><span class="badge success">{{ $account->status }}</span></td>
                            <td class="table-meta">
                                @if (filled($account->password_changed_at))
                                    {{ \Illuminate\Support\Carbon::parse($account->password_changed_at)->translatedFormat('d M Y') }}
                                @else
                                    Belum pernah
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-row">
                                Daftar akun belum dapat ditampilkan. Hubungi Admin sistem.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{--
            Pengaturan ulang kata sandi diletakkan di sini, terlipat.

            Sistem ini tidak mengirim surel, jadi tidak ada tautan "lupa kata
            sandi" yang dapat dikirim kepada petugas — padahal halaman masuk
            mengarahkan mereka menghubungi Admin. Tanpa bagian ini, arahan itu
            tidak dapat ditindaklanjuti.

            Dibuat terlipat agar tidak menambah tinggi halaman saat tidak
            dipakai, dan agar tidak terpakai karena salah klik.
        --}}
        <details class="reset-sandi">
            <summary>
                <x-icon name="shield" class="chip-icon" />
                Atur ulang kata sandi sebuah akun
            </summary>

            <form method="POST" action="" id="formResetSandi" autocomplete="off" style="margin-top:14px;">
                @csrf
                @method('PUT')

                <div class="double-grid" style="margin-top:0;">
                    <div>
                        <label class="table-meta" for="resetAkun">Akun</label>
                        <select class="control" id="resetAkun" required>
                            <option value="">Pilih akun…</option>
                            @foreach ($accounts as $account)
                                <option value="{{ route('settings.password.reset', $account->id) }}">
                                    {{ $account->name }} — {{ $account->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="table-meta" for="resetSandi">Kata Sandi Baru</label>
                        <input class="control" type="password" name="password" id="resetSandi"
                               autocomplete="new-password" placeholder="Minimal {{ $minimum }} karakter"
                               minlength="{{ $minimum }}" maxlength="72" required>
                    </div>
                    <div>
                        <label class="table-meta" for="resetSandiUlang">Ulangi Kata Sandi Baru</label>
                        <input class="control" type="password" name="password_confirmation" id="resetSandiUlang"
                               autocomplete="new-password" placeholder="Ketik ulang kata sandi baru"
                               minlength="{{ $minimum }}" maxlength="72" required>
                    </div>
                </div>

                <p class="table-meta" style="margin-top:12px;">
                    Kata sandi baru tidak dapat dibaca kembali setelah disimpan. Sampaikan
                    langsung kepada pemilik akun, dan minta ia menggantinya sendiri lewat
                    menu Ganti Kata Sandi. Penguncian akibat percobaan masuk yang gagal
                    ikut dilepas.
                </p>

                <button class="btn btn-primary" type="submit" style="margin-top:14px;">
                    <x-icon name="check" class="chip-icon" /> Simpan Kata Sandi Baru
                </button>
            </form>

            <script>
                // Tujuan formulir mengikuti akun yang dipilih, sehingga id akun
                // tidak perlu dikirim sebagai isian yang dapat diubah-ubah.
                (() => {
                    const form = document.getElementById('formResetSandi');
                    const pilihan = document.getElementById('resetAkun');

                    pilihan?.addEventListener('change', () => { form.action = pilihan.value; });
                })();
            </script>
        </details>
    </section>

    <section class="double-grid">
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="shield" class="chip-icon" />Hak Akses</span>
                    <h3>Kewenangan tiap peran</h3>
                    <p class="section-intro">Daftar tindakan yang boleh dilakukan masing-masing peran.</p>
                </div>
            </div>
            <div class="list-group">
                @php
                    $abilityLabels = [
                        'full-access' => 'Akses penuh',
                        'manage-refugees' => 'Kelola data pengungsi',
                        'manage-documents' => 'Kelola dokumen',
                        'manage-placements' => 'Kelola penempatan',
                        'manage-reports' => 'Kelola laporan',
                        'view-reports' => 'Lihat laporan',
                        'manage-settings' => 'Kelola pengaturan',
                        'review-changes' => 'Tinjau perubahan data',
                        'verify-documents' => 'Verifikasi dokumen',
                    ];
                @endphp
                @foreach ($roles as $key => $role)
                    <article class="list-item">
                        <strong>{{ $role['label'] }}</strong>
                        <p>{{ collect($role['abilities'])->map(fn ($a) => $abilityLabels[$a] ?? $a)->implode(' • ') }}</p>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="history" class="chip-icon" />Alur Kerja</span>
                    <h3>Pembagian tugas antar peran</h3>
                    <p class="section-intro">Urutan penanganan data dari input sampai finalisasi.</p>
                </div>
            </div>
            <div class="timeline">
                @foreach ($roleFlow as $item)
                    <article class="timeline-item">
                        <div class="timeline-mark"><x-icon name="users" class="section-icon" /></div>
                        <div>
                            <strong>{{ $item['step'] }}</strong>
                            <p>{{ $item['description'] }}</p>
                            <div class="timeline-meta">{{ $item['actor'] }}</div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
