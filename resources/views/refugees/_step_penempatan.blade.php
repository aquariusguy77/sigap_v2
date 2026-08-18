<div class="wizard-panel" data-step-panel="3" style="margin-top:18px;display:none;">
    <div class="double-grid" style="margin-top:0;">
        <div>
            <label class="table-meta">Lokasi Aktif</label>
            <select class="control" name="location" required>
                <option value="">Pilih lokasi</option>
                @foreach ($locationOptions as $item)
                    <option value="{{ $item }}" @selected($selectedLocation === $item)>{{ $item }}</option>
                @endforeach
            </select>
            @error('location')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="table-meta">Rencana Mutasi</label>
            <input class="control" type="text" value="{{ old('planned_transfer', '') }}" placeholder="Contoh: Transit 1 untuk evaluasi" disabled>
        </div>
    </div>
    <div class="subtle-box">
        Langkah penempatan penuh sudah disiapkan lewat modul terpisah pada menu Penempatan agar data mutasi tetap rapi dan terlacak.
    </div>
</div>
