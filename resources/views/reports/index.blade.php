@extends('layouts.app')

@section('content')
    <section class="panel section-anchor" id="laporan">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="report" class="chip-icon" />Laporan</span>
                <h3>Unduh rekap operasional</h3>
                <p class="section-intro">
                    PDF untuk arsip resmi berkop instansi, CSV untuk diolah di Excel.
                    Isinya mengikuti data terbaru saat tombol unduh ditekan.
                </p>
            </div>
            <span class="badge">{{ count($reports) }} jenis laporan</span>
        </div>

        <div class="report-grid" style="margin-top:0;">
            @foreach ($reports as $report)
                <article class="report-card">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                        <div style="min-width:0;">
                            <strong>{{ $report['name'] }}</strong>
                            <p>{{ $report['note'] }}</p>
                        </div>
                        <div class="stat-icon-wrap"><x-icon :name="$report['icon']" class="stat-icon" /></div>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:12px;">
                        <span class="badge">{{ $report['count'] }} baris</span>
                        <a class="btn btn-gold btn-sm" href="{{ $report['pdf_url'] }}">
                            <x-icon name="download" class="chip-icon" /> PDF
                        </a>
                        <a class="btn btn-primary btn-sm" href="{{ $report['csv_url'] }}">
                            <x-icon name="download" class="chip-icon" /> CSV
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="panel" style="margin-top:14px;">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="history" class="chip-icon" />Riwayat Unduhan</span>
                <h3>Laporan yang pernah diunduh</h3>
                <p class="section-intro">Tercatat otomatis setiap kali laporan diunduh.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Jenis Laporan</th>
                        <th>Format</th>
                        <th>Keterangan</th>
                        <th>Pelaksana</th>
                        <th>Waktu Unduh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportLogs as $log)
                        <tr>
                            <td><span class="cell-title">{{ $log['type'] }}</span></td>
                            <td><span class="badge">{{ $log['format'] }}</span></td>
                            <td class="table-meta">{{ $log['filters'] }}</td>
                            <td>{{ $log['actor'] }}</td>
                            <td class="table-meta">{{ $log['downloaded_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-row">
                                Belum ada laporan yang diunduh. Riwayat akan muncul di sini setelah Anda mengunduh salah satu laporan di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
