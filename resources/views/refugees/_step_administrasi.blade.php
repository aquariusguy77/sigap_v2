<div class="wizard-panel" data-step-panel="2" style="margin-top:18px;display:none;">
    <div class="double-grid" style="margin-top:0;">
        <div>
            <label class="table-meta">Status</label>
            <select class="control" name="status" required>
                <option value="">Pilih status</option>
                @foreach ($statusOptions as $item)
                    <option value="{{ $item }}" @selected($selectedStatus === $item)>{{ $item }}</option>
                @endforeach
            </select>
            @error('status')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="table-meta">Tanggal Registrasi</label>
            <input class="control" type="date" name="registered_at" value="{{ old('registered_at', optional($refugee->registered_at ?? null)->format('Y-m-d')) }}">
            @error('registered_at')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div style="grid-column:1/-1;">
            <label class="table-meta">Catatan</label>
            <textarea class="control" name="notes" rows="5" style="width:100%;resize:vertical;" maxlength="1000">{{ old('notes', $refugee->notes ?? '') }}</textarea>
            @error('notes')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
