<div class="wizard-panel" data-step-panel="4" style="margin-top:18px;display:none;">
    <div class="subtle-box" style="margin-top:0;">
        <h4>Berkas yang dikumpulkan</h4>
        <ul>
            <li>Unggah berkasnya sekarang, atau lewati dan tambahkan nanti lewat menu <strong>Dokumen</strong>.</li>
            <li>Format yang diterima: PDF, JPG, atau PNG, maksimal 5 MB per berkas.</li>
            <li>Berkas yang baru diunggah berstatus <strong>Perlu Verifikasi</strong> sampai supervisor memeriksanya.</li>
        </ul>
    </div>

    <div class="double-grid" style="margin-top:18px;">
        @foreach ($documentTypeOptions as $index => $type)
            <div>
                <label class="table-meta">{{ $type }}</label>
                <input type="hidden" name="documents[{{ $index }}][type]" value="{{ $type }}">
                <input class="control" type="file" name="documents[{{ $index }}][file]" accept=".pdf,.jpg,.jpeg,.png">
                @error('documents.' . $index . '.file')
                    <div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>
                @enderror
            </div>
        @endforeach
    </div>

    @if (! empty($documentStatusOptions))
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:18px;align-items:center;">
            <span class="table-meta">Status kelengkapan yang tersedia:</span>
            @foreach ($documentStatusOptions as $item)
                <span class="badge">{{ $item }}</span>
            @endforeach
        </div>
    @endif
</div>
