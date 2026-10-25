@extends('layouts.app')

@section('content')
    <section class="hero-panel">
        <div class="hero-copy">
            <span class="eyebrow"><x-icon name="users" class="chip-icon" />Profil Pengungsi</span>
            <h3>{{ $refugee->name }}</h3>
            <p>ID internal {{ $refugee->internal_id }} • {{ $refugee->nationality }} • Status {{ $refugee->status }}</p>
            <div class="hero-meta">
                <div><strong>{{ $refugee->unhcr_number ?: '-' }}</strong><span>nomor UNHCR</span></div>
                <div><strong>{{ $refugee->location ?: '-' }}</strong><span>lokasi aktif</span></div>
                <div><strong>{{ optional($refugee->registered_at)->format('d M Y') ?: '-' }}</strong><span>tanggal registrasi</span></div>
            </div>
        </div>
        <div class="hero-side">
            <div class="highlight-card">
                <div class="highlight-head">
                    <strong>Tindakan</strong>
                    <span class="mini-badge success">Aksi tersedia</span>
                </div>
                <p><a href="{{ route('refugees.index') }}" style="color:#fff;">Kembali ke daftar pengungsi</a></p>
                @if ($canManageRefugees)
                    <p><a href="{{ route('refugees.edit', $refugee) }}" style="color:#fff;">Ubah data pengungsi</a></p>
                @endif
                @if ($canDeleteRefugees)
                    <form method="POST" action="{{ route('refugees.destroy', $refugee) }}" style="margin-top:12px;" onsubmit="return confirm('Hapus data {{ addslashes($refugee->name) }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="control" style="width:100%;cursor:pointer;background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.15);">Hapus Data</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    @if ($refugee->status === 'Perlu Verifikasi' && filled($refugee->verification_note))
        <section class="panel" style="border-left:4px solid var(--danger);">
            <span class="section-tag"><x-icon name="alert" class="chip-icon" />Dikembalikan Supervisor</span>
            <p style="margin-top:8px;">{{ $refugee->verification_note }}</p>
            <p class="table-meta">{{ $refugee->verified_by ?: 'Supervisor' }}. Perbaiki lewat <strong>Ubah data pengungsi</strong>, lalu simpan; data akan diperiksa ulang.</p>
        </section>
    @endif

    @if ($canVerifyRefugees)
        <section class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="shield" class="chip-icon" />Verifikasi Supervisor</span>
                    <h3>Periksa data pengungsi ini</h3>
                    <p class="section-intro">Cocokkan identitas, tanggal registrasi, dan lokasi hunian dengan berkas aslinya, lalu terima atau kembalikan ke petugas. Berkas dokumen diverifikasi terpisah di menu Dokumen.</p>
                </div>
                <span class="badge">{{ $refugee->status }}</span>
            </div>
            <form method="POST" action="{{ route('refugees.verify', $refugee) }}">
                @csrf
                @method('PATCH')
                <label class="table-meta" for="kolom-verification-note">Catatan untuk petugas (wajib bila dikembalikan)</label>
                <textarea id="kolom-verification-note" class="control" name="verification_note" rows="3" style="width:100%;resize:vertical;" maxlength="1000">{{ old('verification_note') }}</textarea>
                @error('verification_note')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
                @error('decision')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:18px;">
                    <button class="btn btn-gold" type="submit" name="decision" value="Aktif">Terima — Aktif</button>
                    <button class="btn btn-ghost" type="submit" name="decision" value="Perlu Verifikasi">Kembalikan ke petugas</button>
                </div>
            </form>
        </section>
    @endif

    @if (filled($placement))
        @include('partials._peta-lokasi', ['peta' => $placement, 'judul' => 'Menuju tempat tinggal ' . $refugee->name])
    @endif

    <section class="triple-grid">
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="dashboard" class="chip-icon" />Identitas</span>
                    <h3>Data inti</h3>
                </div>
            </div>
            <div class="list-group">
                <article class="list-item"><strong>Kebangsaan</strong><p>{{ $refugee->nationality }}</p></article>
                <article class="list-item"><strong>Nomor Telepon</strong><p>{{ $refugee->phone ?: 'Tidak ada nomor yang dapat dihubungi.' }}</p></article>
                <article class="list-item"><strong>Status</strong><p>{{ $refugee->status }}</p></article>
                <article class="list-item"><strong>Catatan</strong><p>{{ $refugee->notes ?: 'Belum ada catatan.' }}</p></article>
            </div>
        </div>
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="location" class="chip-icon" />Penempatan</span>
                    <h3>Lokasi aktif</h3>
                </div>
            </div>
            <div class="list-group">
                <article class="list-item"><strong>Lokasi</strong><p>{{ $placement['location_name'] ?? ($refugee->location ?: '-') }}</p></article>
                <article class="list-item"><strong>Kategori</strong><p>{{ $placement['category_label'] ?? '-' }}</p></article>
                <article class="list-item"><strong>Status</strong><p>{{ $placement['placement_status'] ?? '-' }}</p></article>
                <article class="list-item"><strong>Catatan</strong><p>{{ $placement['notes'] ?? 'Belum ada data penempatan.' }}</p></article>
            </div>
        </div>
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="folder" class="chip-icon" />Dokumen</span>
                    <h3>Ringkasan berkas</h3>
                </div>
            </div>
            <div class="list-group">
                <article class="list-item"><strong>Jenis</strong><p>{{ $document['document_type'] ?? ($document['name'] ?? '-') }}</p></article>
                <article class="list-item"><strong>Status Verifikasi</strong><p>{{ $document['verification_status'] ?? ($document['status'] ?? '-') }}</p></article>
                <article class="list-item"><strong>Penyimpanan</strong><p>{{ $document['file_path'] ?? ($document['storage'] ?? '-') }}</p></article>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="history" class="chip-icon" />Riwayat</span>
                <h3>Riwayat perubahan data ini</h3>
            </div>
        </div>
        <div class="timeline">
            @forelse ($history as $item)
                <article class="timeline-item">
                    <div class="timeline-mark"><x-icon name="history" class="section-icon" /></div>
                    <div>
                        <strong>{{ $item['title'] }}</strong>
                        <p>{{ $item['detail'] }}</p>
                        <div class="timeline-meta">{{ $item['actor'] }} • {{ $item['time'] }}</div>
                    </div>
                </article>
            @empty
                <article class="list-item"><p>Belum ada riwayat perubahan untuk data ini.</p></article>
            @endforelse
        </div>
    </section>
@endsection
