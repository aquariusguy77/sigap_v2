@php
    $selectedRefugee = old('refugee_id', $placement->refugee_id ?? '');
    $selectedStatus = old('placement_status', $placement->placement_status ?? '');
@endphp

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="location" class="chip-icon" />Form Penempatan</span>
                <h3>Data lokasi dan status penempatan</h3>
                <p class="section-intro">Gunakan form ini untuk mencatat hunian aktif, histori mutasi, dan catatan operasional penempatan.</p>
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
                <label class="table-meta">Lokasi</label>
                <input class="control" type="text" name="location_name" value="{{ old('location_name', $placement->location_name ?? '') }}" required>
                @error('location_name')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Tanggal Masuk</label>
                <input class="control" type="date" name="entered_at" value="{{ old('entered_at', optional($placement->entered_at ?? null)->format('Y-m-d')) }}" required>
                @error('entered_at')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Tanggal Keluar</label>
                <input class="control" type="date" name="exited_at" value="{{ old('exited_at', optional($placement->exited_at ?? null)->format('Y-m-d')) }}">
                @error('exited_at')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Status</label>
                <select class="control" name="placement_status" required>
                    <option value="">Pilih status</option>
                    @foreach ($statusOptions as $item)
                        <option value="{{ $item }}" @selected($selectedStatus === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                @error('placement_status')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
        </div>
        <div style="margin-top:18px;">
            <label class="table-meta">Catatan</label>
            <textarea class="control" name="notes" rows="4" style="width:100%;resize:vertical;" maxlength="1000">{{ old('notes', $placement->notes ?? '') }}</textarea>
            @error('notes')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;">
            <button class="btn btn-gold" type="submit">
                <x-icon name="check" class="chip-icon" /> Simpan Penempatan
            </button>
            <a class="btn btn-ghost" href="{{ route('placements.index') }}">Batal</a>
        </div>
    </section>
</form>
