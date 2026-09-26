@extends('layouts.app')

@section('content')
    <section class="panel section-anchor" id="hak-akses">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="shield" class="chip-icon" />Hak Akses</span>
                <h3>Akun dan kewenangan</h3>
                <p class="section-intro">
                    Halaman ini hanya menampilkan keadaan sistem; tidak ada yang dapat diubah dari sini.
                    Penambahan akun dilakukan lewat perintah <code>php artisan sigap:seed</code>,
                    sedangkan daftar acuan seperti jenis dokumen dan lokasi hunian diatur pada
                    <code>config/sigap.php</code>.
                </p>
            </div>
            <span class="badge">Peran aktif: {{ $currentRole['label'] }}</span>
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
