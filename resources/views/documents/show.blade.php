@extends('layouts.app')

@section('content')
    <section class="hero-panel">
        <div class="hero-copy">
            <span class="eyebrow"><x-icon name="folder" class="chip-icon" />Detail Dokumen</span>
            <h3>{{ $documentView['document_type'] ?? ($documentView['name'] ?? 'Dokumen') }}</h3>
            <p>{{ $refugeeView['name'] ?? 'Tanpa pengungsi' }} • {{ $documentView['verification_status'] ?? ($documentView['status'] ?? '-') }}</p>
            <div class="hero-meta">
                <div><strong>{{ $refugeeView['internal_id'] ?? '-' }}</strong><span>ID internal pengungsi</span></div>
                <div><strong>{{ $documentView['file_name'] ?? '-' }}</strong><span>nama file</span></div>
                <div><strong>{{ $documentView['uploaded_at'] ?? '-' }}</strong><span>waktu unggah</span></div>
            </div>
        </div>
        <div class="hero-side">
            <div class="highlight-card">
                <div class="highlight-head">
                    <strong>Tindakan</strong>
                    <span class="mini-badge success">Siap diverifikasi</span>
                </div>
                <p><a href="{{ route('documents.index') }}" style="color:#fff;">Kembali ke daftar dokumen</a></p>
                @if ($canManageDocuments)
                    <p><a href="{{ route('documents.edit', $document) }}" style="color:#fff;">Ubah dokumen</a></p>
                @endif
                @if ($canDeleteDocuments)
                    <form method="POST" action="{{ route('documents.destroy', $document) }}" style="margin-top:12px;" onsubmit="return confirm('Hapus dokumen ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="control" style="width:100%;cursor:pointer;background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.15);">Hapus Dokumen</button>
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
                    <h3>Profil singkat</h3>
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
                    <span class="section-tag"><x-icon name="file" class="chip-icon" />Keterangan Dokumen</span>
                    <h3>Penyimpanan dan verifikasi</h3>
                </div>
            </div>
            <div class="list-group">
                <article class="list-item">
                    <strong>Berkas Tersimpan</strong>
                    <p>{{ $documentView['file_path'] ?? ($documentView['storage'] ?? '-') }}</p>
                    @if (!empty($documentView['download_url']))
                        <p style="margin-top:10px;">
                            <a class="btn btn-primary btn-sm" href="{{ $documentView['download_url'] }}"
                               target="_blank" rel="noopener">
                                <x-icon name="download" class="chip-icon" /> Unduh berkas
                            </a>
                        </p>
                    @else
                        <p style="margin-top:8px;" class="table-meta">
                            Berkas belum diunggah ke penyimpanan, atau diunggah sebelum fitur unduh tersedia.
                        </p>
                    @endif
                </article>
                <article class="list-item"><strong>Kode Referensi Berkas</strong><p>{{ $documentView['drive_file_id'] ?? '-' }}</p></article>
                
            </div>
        </div>
    </section>
@endsection
