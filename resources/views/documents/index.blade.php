@extends('layouts.app')

@php
    $badgeClass = fn (string $value) => match ($value) {
        'Lengkap', 'Aktif' => 'success',
        'Perlu Verifikasi', 'Verifikasi' => 'warn',
        default => 'danger',
    };
@endphp

@section('content')
    <section class="panel section-anchor" id="dokumen">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="folder" class="chip-icon" />Dokumen</span>
                <h3>Dokumen pendukung pengungsi</h3>
                <p class="section-intro">Berkas identitas, administrasi, dan lampiran beserta status verifikasinya.</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span class="badge">{{ $documents->total() }} dokumen</span>
                @if ($canManageDocuments)
                    <a class="btn btn-gold btn-sm" href="{{ route('documents.create') }}">
                        <x-icon name="upload" class="chip-icon" /> Unggah Dokumen
                    </a>
                @endif
            </div>
        </div>

        <form class="filters" method="GET" action="{{ route('documents.index') }}">
            <div class="field">
                <label class="field-label" for="d-keyword">Cari</label>
                <input class="control" id="d-keyword" type="text" name="keyword"
                       value="{{ $activeFilters['keyword'] }}" placeholder="Nama berkas / pengungsi">
            </div>
            <div class="field">
                <label class="field-label" for="d-type">Jenis</label>
                <select class="control" id="d-type" name="type">
                    <option value="">Semua</option>
                    @foreach ($documentTypes as $item)
                        <option value="{{ $item }}" @selected($activeFilters['type'] === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="d-status">Status</label>
                <select class="control" id="d-status" name="status">
                    <option value="">Semua</option>
                    @foreach ($statusOptions as $item)
                        <option value="{{ $item }}" @selected($activeFilters['status'] === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="d-perpage">Tampil</label>
                <select class="control" id="d-perpage" name="per_page">
                    @foreach ([5, 10, 15, 20] as $size)
                        <option value="{{ $size }}" @selected((string) $activeFilters['per_page'] === (string) $size)>{{ $size }} baris</option>
                    @endforeach
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">
                    <x-icon name="search" class="chip-icon" /> Cari
                </button>
                <a class="btn btn-ghost" href="{{ route('documents.index') }}">Reset</a>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Dokumen</th>
                        <th>Pengungsi &amp; Berkas</th>
                        <th>Status</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td>
                                @if (!empty($document['id']))
                                    <a class="cell-title" href="{{ route('documents.show', $document['id']) }}">{{ $document['name'] }}</a>
                                @else
                                    <span class="cell-title">{{ $document['name'] }}</span>
                                @endif
                            </td>
                            <td class="table-meta">{{ $document['meta'] }}</td>
                            <td><span class="badge {{ $badgeClass($document['status']) }}">{{ $document['status'] }}</span></td>
                            <td>
                                @if (!empty($document['id']))
                                    <div class="row-actions" style="justify-content:flex-end;">
                                        @if (!empty($document['download_url']))
                                            <a class="btn-icon" href="{{ $document['download_url'] }}"
                                               target="_blank" rel="noopener" title="Unduh berkas">
                                                <x-icon name="download" />
                                            </a>
                                        @endif
                                        <a class="btn-icon" href="{{ route('documents.show', $document['id']) }}" title="Lihat detail">
                                            <x-icon name="eye" />
                                        </a>
                                        @if ($canManageDocuments)
                                            <a class="btn-icon" href="{{ route('documents.edit', $document['id']) }}" title="Ubah dokumen">
                                                <x-icon name="edit" />
                                            </a>
                                        @endif
                                        @if ($canDeleteDocuments)
                                            <form method="POST" action="{{ route('documents.destroy', $document['id']) }}"
                                                  onsubmit="return confirm('Hapus dokumen ini? Tindakan ini tidak bisa dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon danger" title="Hapus dokumen">
                                                    <x-icon name="trash" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">Belum ada dokumen yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-toolbar" style="margin-top:12px;align-items:center;">
            <p>Menampilkan {{ $documents->firstItem() ?? 0 }}–{{ $documents->lastItem() ?? 0 }} dari {{ $documents->total() }} dokumen</p>
            <div class="pager">
                @if ($documents->onFirstPage())
                    <span class="is-disabled">Sebelumnya</span>
                @else
                    <a href="{{ $documents->previousPageUrl() }}">Sebelumnya</a>
                @endif
                <span class="is-current">{{ $documents->currentPage() }} / {{ $documents->lastPage() }}</span>
                @if ($documents->hasMorePages())
                    <a href="{{ $documents->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="is-disabled">Berikutnya</span>
                @endif
            </div>
        </div>
    </section>
@endsection
