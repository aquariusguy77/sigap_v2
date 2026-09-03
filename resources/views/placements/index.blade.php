@extends('layouts.app')

@php
    $badgeClass = fn (string $value) => match ($value) {
        'Aktif' => 'success',
        'Perlu Verifikasi' => 'warn',
        default => 'danger',
    };
@endphp

@section('content')
    <section class="panel section-anchor" id="penempatan">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="location" class="chip-icon" />Penempatan</span>
                <h3>Hunian dan riwayat mutasi</h3>
                <p class="section-intro">Catatan lokasi hunian aktif beserta tanggal masuk dan keluar tiap pengungsi.</p>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span class="badge">{{ $placements->total() }} penempatan</span>
                @if ($canManagePlacements)
                    <a class="btn btn-gold btn-sm" href="{{ route('placements.create') }}">
                        <x-icon name="plus" class="chip-icon" /> Tambah Penempatan
                    </a>
                @endif
            </div>
        </div>

        <form class="filters" method="GET" action="{{ route('placements.index') }}">
            <div class="field">
                <label class="field-label" for="p-keyword">Cari</label>
                <input class="control" id="p-keyword" type="text" name="keyword"
                       value="{{ $activeFilters['keyword'] }}" placeholder="Nama atau lokasi">
            </div>
            <div class="field">
                <label class="field-label" for="p-status">Status</label>
                <select class="control" id="p-status" name="status">
                    <option value="">Semua</option>
                    @foreach ($statusOptions as $item)
                        <option value="{{ $item }}" @selected($activeFilters['status'] === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="p-perpage">Tampil</label>
                <select class="control" id="p-perpage" name="per_page">
                    @foreach ([5, 10, 15, 20] as $size)
                        <option value="{{ $size }}" @selected((string) $activeFilters['per_page'] === (string) $size)>{{ $size }} baris</option>
                    @endforeach
                </select>
            </div>
            <div class="filters-actions">
                <button class="btn btn-primary" type="submit">
                    <x-icon name="search" class="chip-icon" /> Cari
                </button>
                <a class="btn btn-ghost" href="{{ route('placements.index') }}">Reset</a>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Pengungsi</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th>Status</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($placements as $placement)
                        <tr>
                            <td>
                                @if (!empty($placement['id']))
                                    <a class="cell-title" href="{{ route('placements.show', $placement['id']) }}">{{ $placement['title'] }}</a>
                                @else
                                    <span class="cell-title">{{ $placement['title'] }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ !empty($placement['is_mandiri']) ? '' : 'success' }}">
                                    {{ $placement['category_label'] ?? '-' }}
                                </span>
                            </td>
                            <td>{{ $placement['location_name'] ?? '-' }}</td>
                            <td class="table-meta">{{ $placement['entered_at'] ?? '-' }}</td>
                            <td class="table-meta">{{ $placement['exited_at'] ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $badgeClass($placement['placement_status'] ?? '') }}">
                                    {{ $placement['placement_status'] ?? '-' }}
                                </span>
                            </td>
                            <td>
                                @if (!empty($placement['id']))
                                    <div class="row-actions" style="justify-content:flex-end;">
                                        @if (!empty($placement['directions_url']))
                                            <a class="btn-icon" href="{{ $placement['directions_url'] }}" target="_blank" rel="noopener"
                                               title="Petunjuk arah ke rumah pengungsi">
                                                <x-icon name="location" />
                                            </a>
                                        @endif
                                        <a class="btn-icon" href="{{ route('placements.show', $placement['id']) }}" title="Lihat detail">
                                            <x-icon name="eye" />
                                        </a>
                                        @if ($canManagePlacements)
                                            <a class="btn-icon" href="{{ route('placements.edit', $placement['id']) }}" title="Ubah penempatan">
                                                <x-icon name="edit" />
                                            </a>
                                        @endif
                                        @if ($canDeletePlacements)
                                            <form method="POST" action="{{ route('placements.destroy', $placement['id']) }}"
                                                  onsubmit="return confirm('Hapus penempatan ini? Tindakan ini tidak bisa dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon danger" title="Hapus penempatan">
                                                    <x-icon name="trash" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-row">Belum ada penempatan yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-toolbar" style="margin-top:12px;align-items:center;">
            <p>Menampilkan {{ $placements->firstItem() ?? 0 }}–{{ $placements->lastItem() ?? 0 }} dari {{ $placements->total() }} data</p>
            <div class="pager">
                @if ($placements->onFirstPage())
                    <span class="is-disabled">Sebelumnya</span>
                @else
                    <a href="{{ $placements->previousPageUrl() }}">Sebelumnya</a>
                @endif
                <span class="is-current">{{ $placements->currentPage() }} / {{ $placements->lastPage() }}</span>
                @if ($placements->hasMorePages())
                    <a href="{{ $placements->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="is-disabled">Berikutnya</span>
                @endif
            </div>
        </div>
    </section>
@endsection
