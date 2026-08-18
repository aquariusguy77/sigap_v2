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
                <article class="list-item"><strong>Lokasi</strong><p>{{ $placementView['location_name'] ?? '-' }}</p></article>
                <article class="list-item"><strong>Status Penempatan</strong><p>{{ $placementView['placement_status'] ?? '-' }}</p></article>
                <article class="list-item"><strong>Catatan</strong><p>{{ $placementView['notes'] ?? 'Belum ada catatan.' }}</p></article>
            </div>
        </div>
    </section>
@endsection
