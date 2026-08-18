@extends('layouts.app')

@section('content')
    <section class="panel" style="margin-bottom:14px;">
        <div class="section-head" style="margin-bottom:0;">
            <div>
                <span class="section-tag"><x-icon name="users" class="chip-icon" />Sedang diubah</span>
                <h3>{{ $refugee->name ?? 'Data Pengungsi' }}</h3>
                <p class="section-intro">
                    {{ $refugee->internal_id ?? '-' }} &bull; {{ $refugee->status ?? '-' }} &bull; {{ $refugee->location ?? '-' }}
                </p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="btn btn-ghost btn-sm" href="{{ route('refugees.show', $refugee) }}">
                    <x-icon name="eye" class="chip-icon" /> Lihat detail
                </a>
                <a class="btn btn-ghost btn-sm" href="{{ route('refugees.index') }}">Kembali ke daftar</a>
            </div>
        </div>
    </section>

    @include('refugees._form')
@endsection
