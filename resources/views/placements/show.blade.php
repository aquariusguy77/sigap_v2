@extends('layouts.app')

@section('content')
    <section class="hero-panel">
        <div class="hero-copy">
            <span class="eyebrow"><x-icon name="location" class="chip-icon" />Detail Penempatan</span>
            <h3>{{ $placementView['title'] ?? ($refugeeView['name'] ?? 'Penempatan') }}</h3>
            <p>{{ $placementView['location_name'] ?? '-' }} • {{ $placementView['placement_status'] ?? '-' }}</p>
            <div class="hero-meta">
                <div><strong>{{ $refugeeView['internal_id'] ?? '-' }}</strong><span>ID internal pengungsi</span></div>
                <div><strong>{{ $placementView['entered_at'] ?? '-' }}</strong><span>tanggal masuk</span></div>
                <div><strong>{{ $placementView['exited_at'] ?? '-' }}</strong><span>tanggal keluar</span></div>
            </div>
        </div>
        <div class="hero-side">
            <div class="highlight-card">
                <div class="highlight-head">
                    <strong>Tindakan</strong>
                    <span class="mini-badge success">Siap diperbarui</span>
                </div>
                <p><a href="{{ route('placements.index') }}" style="color:#fff;">Kembali ke daftar penempatan</a></p>
                @if ($canManagePlacements)
                    <p><a href="{{ route('placements.edit', $placement) }}" style="color:#fff;">Ubah penempatan</a></p>
                @endif
                @if ($canDeletePlacements)
                    <form method="POST" action="{{ route('placements.destroy', $placement) }}" style="margin-top:12px;" onsubmit="return confirm('Hapus penempatan ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="control" style="width:100%;cursor:pointer;background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.15);">Hapus Penempatan</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    @if (!empty($placementView['has_map']))
        <section class="panel" style="margin-top:14px;">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="location" class="chip-icon" />Pengawasan Lapangan</span>
                    <h3>Menuju tempat tinggal pengungsi</h3>
                    <p class="section-intro">
                        Pengungsi mandiri tinggal menyebar, tidak terpusat seperti di Community House.
                        Buka petunjuk arah di bawah ini langsung dari ponsel saat hendak berangkat.
                    </p>
                </div>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a class="btn btn-primary" href="{{ $placementView['directions_url'] }}" target="_blank" rel="noopener">
                    <x-icon name="location" class="chip-icon" /> Petunjuk Arah
                </a>
                <a class="btn btn-ghost" href="{{ $placementView['map_url'] }}" target="_blank" rel="noopener">
                    <x-icon name="search" class="chip-icon" /> Lihat di Peta
                </a>
            </div>
            @if (blank($placementView['latitude'] ?? null))
                <p class="table-meta" style="margin-top:12px;">
                    Titik peta masih dicari berdasarkan alamat. Agar tepat sasaran, lengkapi lintang dan bujur lewat menu Ubah Penempatan.
                </p>
            @endif
        </section>
    @endif

    <section class="double-grid">
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="users" class="chip-icon" />Pengungsi Terkait</span>
                    <h3>Ringkasan data pengungsi</h3>
                </div>
            </div>
            <div class="list-group">
                <article class="list-item"><strong>Nama</strong><p>{{ $refugeeView['name'] ?? '-' }}</p></article>
                <article class="list-item"><strong>Kebangsaan</strong><p>{{ $refugeeView['nationality'] ?? '-' }}</p></article>
                <article class="list-item"><strong>Status</strong><p>{{ $refugeeView['status'] ?? '-' }}</p></article>
            </div>
        </div>
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="history" class="chip-icon" />Catatan Penempatan</span>
                    <h3>Informasi operasional</h3>
                </div>
            </div>
            <div class="list-group">
                <article class="list-item"><strong>Kategori</strong><p>{{ $placementView['category_label'] ?? '-' }}</p></article>
                @if (!empty($placementView['is_mandiri']))
                    <article class="list-item">
                        <strong>Alamat Tempat Tinggal</strong>
                        <p>{{ $placementView['address'] ?: 'Belum dicatat.' }}</p>
                    </article>
                    @if (filled($placementView['latitude'] ?? null) && filled($placementView['longitude'] ?? null))
                        <article class="list-item">
                            <strong>Koordinat</strong>
                            <p>{{ $placementView['latitude'] }}, {{ $placementView['longitude'] }}</p>
                        </article>
                    @endif
                @else
                    <article class="list-item"><strong>Community House</strong><p>{{ $placementView['community_house'] ?: '-' }}</p></article>
                @endif
                <article class="list-item"><strong>Status Penempatan</strong><p>{{ $placementView['placement_status'] ?? '-' }}</p></article>
                <article class="list-item"><strong>Catatan</strong><p>{{ $placementView['notes'] ?? 'Belum ada catatan.' }}</p></article>
            </div>
        </div>
    </section>
@endsection
