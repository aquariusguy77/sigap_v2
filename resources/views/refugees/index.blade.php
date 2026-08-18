@extends('layouts.app')

@php
    $badgeClass = fn (string $value) => match ($value) {
        'Lengkap', 'Aktif' => 'success',
        'Perlu Verifikasi', 'Verifikasi' => 'warn',
        default => 'danger',
    };
@endphp

@section('content')
    <section class="panel section-anchor" id="data-pengungsi">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="users" class="chip-icon" />Data Pengungsi</span>
                <h3>Daftar pengungsi terdata</h3>
                <p class="section-intro">Gunakan kolom pencarian dan filter untuk menemukan data dengan cepat.</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span class="badge">{{ $refugees->total() }} data</span>
                @if ($canManageRefugees)
                    <a class="btn btn-gold btn-sm" href="{{ route('refugees.create') }}">
                        <x-icon name="plus" class="chip-icon" /> Tambah Data
                    </a>
                @endif
            </div>
        </div>

        <form class="filters" method="GET" action="{{ route('refugees.index') }}">
            <div class="field">
                <label class="field-label" for="f-keyword">Cari</label>
                <input class="control" id="f-keyword" type="text" name="keyword"
                       value="{{ $activeFilters['keyword'] }}" placeholder="Nama atau ID">
            </div>
            <div class="field">
                <label class="field-label" for="f-nationality">Kebangsaan</label>
                <select class="control" id="f-nationality" name="nationality">
                    <option value="">Semua</option>
                    @foreach ($filterOptions['nationalities'] as $item)
                        <option value="{{ $item }}" @selected($activeFilters['nationality'] === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="f-status">Status</label>
                <select class="control" id="f-status" name="status">
                    <option value="">Semua</option>
                    @foreach ($filterOptions['statuses'] as $item)
                        <option value="{{ $item }}" @selected($activeFilters['status'] === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="f-location">Lokasi</label>
                <select class="control" id="f-location" name="location">
                    <option value="">Semua</option>
                    @foreach ($filterOptions['locations'] as $item)
                        <option value="{{ $item }}" @selected($activeFilters['location'] === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="f-docs">Dokumen</label>
                <select class="control" id="f-docs" name="document_status">
                    <option value="">Semua</option>
                    @foreach ($filterOptions['documentStatuses'] as $item)
                        <option value="{{ $item }}" @selected($activeFilters['document_status'] === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="f-sort">Urutkan</label>
                <select class="control" id="f-sort" name="sort">
                    <option value="name" @selected($activeFilters['sort'] === 'name')>Nama</option>
                    <option value="internal_id" @selected($activeFilters['sort'] === 'internal_id')>ID Internal</option>
                    <option value="nationality" @selected($activeFilters['sort'] === 'nationality')>Kebangsaan</option>
                    <option value="status" @selected($activeFilters['sort'] === 'status')>Status</option>
                    <option value="location" @selected($activeFilters['sort'] === 'location')>Lokasi</option>
                    <option value="updated_at" @selected($activeFilters['sort'] === 'updated_at')>Pembaruan</option>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="f-direction">Arah</label>
                <select class="control" id="f-direction" name="direction">
                    <option value="asc" @selected($activeFilters['direction'] === 'asc')>Naik (A-Z)</option>
                    <option value="desc" @selected($activeFilters['direction'] === 'desc')>Turun (Z-A)</option>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="f-perpage">Tampil</label>
                <select class="control" id="f-perpage" name="per_page">
                    @foreach ([5, 10, 15, 20] as $size)
                        <option value="{{ $size }}" @selected((string) $activeFilters['per_page'] === (string) $size)>{{ $size }} baris</option>
                    @endforeach
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">
                    <x-icon name="search" class="chip-icon" /> Cari
                </button>
                <a class="btn btn-ghost" href="{{ route('refugees.index') }}">Reset</a>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Pengungsi</th>
                        <th>Kebangsaan</th>
                        <th>Status</th>
                        <th>Lokasi</th>
                        <th>Dokumen</th>
                        <th>Pembaruan</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refugees as $refugee)
                        <tr>
                            <td>
                                @if (!empty($refugee['id']))
                                    <a class="cell-title" href="{{ route('refugees.show', $refugee['id']) }}">{{ $refugee['name'] }}</a>
                                @else
                                    <span class="cell-title">{{ $refugee['name'] }}</span>
                                @endif
                                <span class="table-meta">{{ $refugee['internal_id'] }}</span>
                            </td>
                            <td>{{ $refugee['nationality'] }}</td>
                            <td><span class="badge {{ $badgeClass($refugee['status']) }}">{{ $refugee['status'] }}</span></td>
                            <td>{{ $refugee['location'] }}</td>
                            <td><span class="badge {{ $badgeClass($refugee['document_status']) }}">{{ $refugee['document_status'] }}</span></td>
                            <td class="table-meta">{{ $refugee['updated_at_label'] }}</td>
                            <td>
                                @if (!empty($refugee['id']))
                                    <div class="row-actions" style="justify-content:flex-end;">
                                        <a class="btn-icon" href="{{ route('refugees.show', $refugee['id']) }}" title="Lihat detail">
                                            <x-icon name="eye" />
                                        </a>
                                        @if ($canManageRefugees)
                                            <a class="btn-icon" href="{{ route('refugees.edit', $refugee['id']) }}" title="Ubah data">
                                                <x-icon name="edit" />
                                            </a>
                                        @endif
                                        @if ($canDeleteRefugees)
                                            <form method="POST" action="{{ route('refugees.destroy', $refugee['id']) }}"
                                                  onsubmit="return confirm('Hapus data {{ addslashes($refugee['name']) }}? Tindakan ini tidak bisa dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon danger" title="Hapus data">
                                                    <x-icon name="trash" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-row">
                                Tidak ada data yang cocok dengan pencarian Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-toolbar" style="margin-top:12px;align-items:center;">
            <p>Menampilkan {{ $refugees->firstItem() ?? 0 }}–{{ $refugees->lastItem() ?? 0 }} dari {{ $refugees->total() }} data</p>
            <div class="pager">
                @if ($refugees->onFirstPage())
                    <span class="is-disabled">Sebelumnya</span>
                @else
                    <a href="{{ $refugees->previousPageUrl() }}">Sebelumnya</a>
                @endif

                <span class="is-current">{{ $refugees->currentPage() }} / {{ $refugees->lastPage() }}</span>

                @if ($refugees->hasMorePages())
                    <a href="{{ $refugees->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="is-disabled">Berikutnya</span>
                @endif
            </div>
        </div>
    </section>
@endsection
