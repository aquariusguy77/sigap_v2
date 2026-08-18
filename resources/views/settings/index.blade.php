@extends('layouts.app')

@section('content')
    <section class="panel section-anchor" id="pengaturan">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="settings" class="chip-icon" />Pengaturan</span>
                <h3>Konfigurasi sistem dan hak akses</h3>
                <p class="section-intro">Pengelolaan akun, keamanan, dan referensi data hanya dapat diakses oleh Admin.</p>
            </div>
            <span class="badge">Peran aktif: {{ $currentRole['label'] }}</span>
        </div>

        <div class="triple-grid settings-grid" style="margin-top:0;">
            <article class="setting-card">
                <div class="section-icon-wrap stat-icon-wrap"><x-icon name="dashboard" class="section-icon" /></div>
                <div>
                    <strong>Master Data</strong>
                    <p>Referensi kebangsaan, jenis dokumen, lokasi hunian, dan status operasional.</p>
                </div>
            </article>
            <article class="setting-card">
                <div class="section-icon-wrap stat-icon-wrap tone-green"><x-icon name="users" class="section-icon" /></div>
                <div>
                    <strong>Hak Akses &amp; Akun</strong>
                    <p>Pengelolaan akun petugas beserta pembagian peran dan kewenangannya.</p>
                </div>
            </article>
            <article class="setting-card">
                <div class="section-icon-wrap stat-icon-wrap tone-orange"><x-icon name="sync" class="section-icon" /></div>
                <div>
                    <strong>Cadangan Data</strong>
                    <p>Jadwal pencadangan berkala dan pemulihan data bila terjadi gangguan.</p>
                </div>
            </article>
            <article class="setting-card">
                <div class="section-icon-wrap stat-icon-wrap tone-deep"><x-icon name="shield" class="section-icon" /></div>
                <div>
                    <strong>Keamanan</strong>
                    <p>Pengaturan sesi, catatan masuk, dan kontrol perubahan data sensitif.</p>
                </div>
            </article>
            <article class="setting-card">
                <div class="section-icon-wrap stat-icon-wrap tone-green"><x-icon name="alert" class="section-icon" /></div>
                <div>
                    <strong>Notifikasi</strong>
                    <p>Peringatan dokumen belum lengkap dan jadwal rekap pelaporan.</p>
                </div>
            </article>
            <article class="setting-card">
                <div class="section-icon-wrap stat-icon-wrap"><x-icon name="file" class="section-icon" /></div>
                <div>
                    <strong>Informasi Sistem</strong>
                    <p>Basis data: Firebase Realtime Database &bull; Penyimpanan berkas: Firebase Storage.</p>
                </div>
            </article>
        </div>
    </section>

    <section class="panel" style="margin-top:14px;">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="users" class="chip-icon" />Akun Pengguna</span>
                <h3>Daftar akun terdaftar</h3>
                <p class="section-intro">Akun tersimpan di Firebase. Kata sandi disimpan dalam bentuk hash dan tidak pernah ditampilkan.</p>
            </div>
            <span class="badge">{{ $accounts->count() }} akun</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td><span class="cell-title">{{ $account->name }}</span></td>
                            <td class="table-meta">{{ $account->email }}</td>
                            <td><span class="badge">{{ $roles[$account->role]['label'] ?? $account->role }}</span></td>
                            <td><span class="badge success">{{ $account->status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-row">
                                Belum ada akun. Jalankan perintah <code>php artisan sigap:seed</code> untuk membuat akun awal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
                        <p>{{ collect($role['abilities'])->map(fn ($a) => $abilityLabels[$a] ?? $a)->implode(' &bull; ') }}</p>
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
