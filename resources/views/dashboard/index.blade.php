@extends('layouts.app')

@php
    $toneClass = fn (string $tone) => match ($tone) {
        'green' => 'tone-green',
        'orange' => 'tone-orange',
        'deep' => 'tone-deep',
        default => '',
    };
    $badgeClass = fn (string $value) => match ($value) {
        'Lengkap', 'Aktif' => 'success',
        'Perlu Verifikasi', 'Verifikasi' => 'warn',
        default => 'danger',
    };
@endphp

@section('content')
    <section class="hero-panel section-anchor" id="dashboard">
        <div class="hero-copy">
            <span class="eyebrow">
                <x-icon name="shield" class="chip-icon" />
                Rudenim Surabaya
            </span>
            <h3>Selamat datang, {{ $currentUser['name'] }}</h3>
            <p>
                Pantau pendataan pengungsi luar negeri di wilayah kerja Rumah Detensi Imigrasi Surabaya
                dalam satu tampilan ringkas.
            </p>
            <div class="hero-meta">
                @foreach ($stats as $stat)
                    <div>
                        <strong>{{ $stat['value'] }}</strong>
                        <span>{{ strtolower($stat['label']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="hero-side">
            <div class="highlight-card">
                <div class="highlight-head">
                    <strong>Perlu tindak lanjut</strong>
                    <span class="mini-badge warn">{{ $stats[2]['value'] ?? 0 }} berkas</span>
                </div>
                <p>Dokumen yang menunggu pemeriksaan supervisor sebelum data dinyatakan lengkap.</p>
            </div>
            @if ($canManageRefugees)
                <a class="btn btn-gold" href="{{ route('refugees.create') }}" style="justify-content:center;">
                    <x-icon name="plus" class="chip-icon" /> Tambah Data Pengungsi
                </a>
            @endif
        </div>
    </section>

    <section class="dashboard-grid" aria-label="Ringkasan statistik">
        @foreach ($stats as $stat)
            <article class="stat-card">
                <div class="stat-head">
                    <h4>{{ $stat['label'] }}</h4>
                    <div class="stat-icon-wrap {{ $toneClass($stat['tone']) }}">
                        <x-icon :name="$stat['icon']" class="stat-icon" />
                    </div>
                </div>
                <strong>{{ $stat['value'] }}</strong>
                <p class="metric-note">{{ $stat['note'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="content-grid">
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="users" class="chip-icon" />Data Terbaru</span>
                    <h3>Pengungsi terakhir diperbarui</h3>
                </div>
                <a class="btn btn-ghost btn-sm" href="{{ route('refugees.index') }}">Lihat semua</a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Pengungsi</th>
                            <th>Kebangsaan</th>
                            <th>Lokasi</th>
                            <th>Status</th>
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
                                <td>{{ $refugee['location'] }}</td>
                                <td><span class="badge {{ $badgeClass($refugee['status']) }}">{{ $refugee['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-row">Belum ada data pengungsi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:grid;gap:14px;">
            <div class="panel">
                <div class="section-head" style="margin-bottom:10px;">
                    <div>
                        <span class="section-tag"><x-icon name="location" class="chip-icon" />Sebaran</span>
                        <h3>Jumlah per lokasi</h3>
                    </div>
                </div>

                <div style="display:grid;gap:9px;">
                    @forelse ($locationSummary as $row)
                        <div>
                            <div style="display:flex;justify-content:space-between;gap:8px;margin-bottom:4px;">
                                <span style="font-size:.79rem;font-weight:500;">{{ $row['location'] }}</span>
                                <span class="table-meta">{{ $row['count'] }}</span>
                            </div>
                            <div style="height:5px;border-radius:3px;background:#eef5f7;overflow:hidden;">
                                <div style="height:100%;width:{{ max(6, $row['percent']) }}%;border-radius:3px;background:linear-gradient(90deg,var(--tosca),var(--gold));"></div>
                            </div>
                        </div>
                    @empty
                        <p class="table-meta" style="margin:0;">Belum ada data lokasi.</p>
                    @endforelse
                </div>
            </div>

            <div class="panel">
                <div class="section-head" style="margin-bottom:10px;">
                    <div>
                        <span class="section-tag"><x-icon name="history" class="chip-icon" />Aktivitas</span>
                        <h3>Perubahan terbaru</h3>
                    </div>
                </div>

                <div class="timeline">
                    @forelse ($activities->take(4) as $item)
                        <article class="timeline-item">
                            <div class="timeline-mark"><x-icon name="history" class="section-icon" /></div>
                            <div>
                                <strong>{{ $item['title'] }}</strong>
                                <p>{{ $item['detail'] }}</p>
                                <div class="timeline-meta">{{ $item['actor'] }} &bull; {{ $item['time'] }}</div>
                            </div>
                        </article>
                    @empty
                        <p class="table-meta" style="margin:0;">Belum ada aktivitas tercatat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
