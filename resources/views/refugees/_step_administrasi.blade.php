<div class="wizard-panel" data-step-panel="2" style="margin-top:18px;display:none;">
    <div class="double-grid" style="margin-top:0;">
        {{--
            Status tidak lagi dipilih petugas. Data baru maupun yang diubah
            otomatis Perlu Verifikasi, lalu Supervisor yang menetapkannya Aktif.
        --}}
        <div>
            <span class="table-meta">Status</span>
            <div class="control" style="display:flex;align-items:center;" aria-readonly="true">
                {{ $refugee->status ?? null ? $refugee->status : 'Perlu Verifikasi' }}
            </div>
            <div class="table-meta" style="margin-top:6px;">Ditetapkan Supervisor. Data baru dan data yang diubah menunggu verifikasi.</div>
        </div>
        <div>
            <label class="table-meta" for="kolom-registered-at">Tanggal Registrasi</label>
            <input id="kolom-registered-at" class="control" type="date" name="registered_at" value="{{ old('registered_at', optional($refugee->registered_at ?? null)->format('Y-m-d')) }}">
            @error('registered_at')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div style="grid-column:1/-1;">
            <label class="table-meta" for="kolom-notes">Catatan</label>
            <textarea id="kolom-notes" class="control" name="notes" rows="5" style="width:100%;resize:vertical;" maxlength="1000">{{ old('notes', $refugee->notes ?? '') }}</textarea>
            @error('notes')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
