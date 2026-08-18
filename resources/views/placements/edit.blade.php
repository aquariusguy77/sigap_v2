@extends('layouts.app')

@section('content')
    <section class="panel" style="margin-bottom:14px;">
        <div class="section-head" style="margin-bottom:0;">
            <div>
                <span class="section-tag"><x-icon name="location" class="chip-icon" />Sedang diubah</span>
                <h3>{{ $placement->location_name ?? 'Data Penempatan' }}</h3>
                <p class="section-intro">Status: {{ $placement->placement_status ?? '-' }}</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="btn btn-ghost btn-sm" href="{{ route('placements.show', $placement) }}">
                    <x-icon name="eye" class="chip-icon" /> Lihat detail
                </a>
                <a class="btn btn-ghost btn-sm" href="{{ route('placements.index') }}">Kembali ke daftar</a>
            </div>
        </div>
    </section>

    @include('placements._form')
@endsection
