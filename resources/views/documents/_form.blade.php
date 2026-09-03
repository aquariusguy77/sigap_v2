@php
    $selectedRefugee = old('refugee_id', $document->refugee_id ?? '');
    $selectedType = old('document_type', $document->document_type ?? '');
    $selectedStatus = old('verification_status', $document->verification_status ?? '');
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="folder" class="chip-icon" />Form Dokumen</span>
                <h3>Keterangan dokumen dan verifikasi</h3>
                <p class="section-intro">Unggah berkas pendukung dan lengkapi keterangannya.</p>
            </div>
            <span class="badge">{{ $formMethod === 'POST' ? 'Data baru' : 'Ubah data' }}</span>
        </div>
        <div class="double-grid" style="margin-top:0;">
            <div>
                <label class="table-meta">Pengungsi</label>
                <select class="control" name="refugee_id" required>
                    <option value="">Pilih pengungsi</option>
                    @foreach ($refugees as $item)
                        <option value="{{ $item['id'] }}" @selected((string) $selectedRefugee === (string) $item['id'])>{{ $item['name'] }} - {{ $item['internal_id'] }}</option>
                    @endforeach
                </select>
                @error('refugee_id')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Jenis Dokumen</label>
                <select class="control" name="document_type" required>
                    <option value="">Pilih jenis</option>
                    @foreach ($documentTypes as $item)
                        <option value="{{ $item }}" @selected($selectedType === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                @error('document_type')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Nama Berkas</label>
                <input class="control" type="text" name="file_name" value="{{ old('file_name', $document->file_name ?? '') }}" required>
                @error('file_name')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Pilih Berkas</label>
                <input class="control" type="file" name="uploaded_file" accept=".pdf,.jpg,.jpeg,.png">
                @error('uploaded_file')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Status Verifikasi</label>
                <select class="control" name="verification_status" required>
                    <option value="">Pilih status</option>
                    @foreach ($statusOptions as $item)
                        <option value="{{ $item }}" @selected($selectedStatus === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                @error('verification_status')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Tanggal Unggah</label>
                <input class="control" type="date" name="uploaded_at" value="{{ old('uploaded_at', optional($document->uploaded_at ?? null)->format('Y-m-d')) }}" required>
                @error('uploaded_at')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
        </div>
        <div style="margin-top:18px;">
            <label class="table-meta">Catatan</label>
            <textarea class="control" name="notes" rows="4" style="width:100%;resize:vertical;" maxlength="1000">{{ old('notes', $document->notes ?? '') }}</textarea>
            @error('notes')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div class="subtle-box">
            <h4>Panduan singkat</h4>
            <ul>
                <li>Format yang diterima: PDF, JPG, atau PNG.</li>
                <li>Nama berkas terisi otomatis setelah file diunggah, jadi tidak perlu diketik.</li>
                <li>Tanpa mengunggah file baru, berkas yang sudah tersimpan tetap dipertahankan.</li>
                <li>Kode referensi boleh dikosongkan; sistem akan membuatkannya.</li>
            </ul>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;">
            <button class="btn btn-gold" type="submit">
                <x-icon name="upload" class="chip-icon" /> Simpan Dokumen
            </button>
            <a class="btn btn-ghost" href="{{ route('documents.index') }}">Batal</a>
        </div>
    </section>
</form>
