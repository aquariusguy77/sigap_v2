@extends('layouts.app')

@section('content')
    <section class="panel" style="margin-bottom:14px;">
        <div class="section-head" style="margin-bottom:0;">
            <div>
                <span class="section-tag"><x-icon name="folder" class="chip-icon" />Sedang diubah</span>
                <h3>{{ $document->document_type ?? 'Data Dokumen' }}</h3>
                <p class="section-intro">Berkas: {{ $document->file_name ?? '-' }}</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="btn btn-ghost btn-sm" href="{{ route('documents.show', $document) }}">
                    <x-icon name="eye" class="chip-icon" /> Lihat detail
                </a>
                <a class="btn btn-ghost btn-sm" href="{{ route('documents.index') }}">Kembali ke daftar</a>
            </div>
        </div>
    </section>

    @include('documents._form')
@endsection
