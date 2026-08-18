@extends('layouts.app')

@section('content')
    <section class="panel section-anchor" id="riwayat">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="history" class="chip-icon" />Riwayat Perubahan</span>
                <h3>Log aktivitas pengubahan data</h3>
                <p class="section-intro">Setiap penambahan, perubahan, dan penghapusan data tercatat lengkap dengan pelaksana dan waktunya.</p>
            </div>
            <span class="badge">{{ $history->count() }} catatan</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Aktivitas</th>
                        <th>Rincian Perubahan</th>
                        <th>Pelaksana</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr>
                            <td><span class="cell-title">{{ $item['title'] }}</span></td>
                            <td class="table-meta">{{ $item['detail'] }}</td>
                            <td>{{ $item['actor'] }}</td>
                            <td class="table-meta">{{ $item['time'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-row">Belum ada perubahan data yang tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-grid">
        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="sync" class="chip-icon" />Terkini</span>
                    <h3>Aktivitas terbaru</h3>
                    <p class="section-intro">Pembaruan paling akhir yang perlu diperhatikan supervisor.</p>
                </div>
            </div>

            <div class="timeline">
                @forelse ($activities as $item)
                    <article class="timeline-item">
                        <div class="timeline-mark"><x-icon name="history" class="section-icon" /></div>
                        <div>
                            <strong>{{ $item['title'] }}</strong>
                            <p>{{ $item['detail'] }}</p>
                            <div class="timeline-meta">{{ $item['actor'] }} &bull; {{ $item['time'] }}</div>
                        </div>
                    </article>
                @empty
                    <p class="table-meta" style="margin:0;">Belum ada aktivitas terbaru.</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="section-head">
                <div>
                    <span class="section-tag"><x-icon name="report" class="chip-icon" />Laporan</span>
                    <h3>Unduhan terakhir</h3>
                    <p class="section-intro">Riwayat laporan yang pernah diunduh petugas.</p>
                </div>
            </div>

            <div class="list-group">
                @forelse (collect($reportLogs)->take(4) as $log)
                    <article class="list-item">
                        <strong>{{ $log['type'] }}</strong>
                        <p>{{ $log['filters'] }}</p>
                        <div class="timeline-meta">{{ $log['actor'] }} &bull; {{ $log['downloaded_at'] }}</div>
                    </article>
                @empty
                    <p class="table-meta" style="margin:0;">Belum ada unduhan laporan.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
