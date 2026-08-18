<div class="wizard-panel" data-step-panel="4" style="margin-top:18px;display:none;">
    <div class="subtle-box" style="margin-top:0;">
        <h4>Dokumen pendukung</h4>
        <ul>
            <li>Simpan data pengungsi ini terlebih dahulu.</li>
            <li>Setelah tersimpan, unggah berkas identitas dan administrasi melalui menu <strong>Dokumen</strong>.</li>
            <li>Status kelengkapan pada daftar akan mengikuti hasil verifikasi dokumen tersebut.</li>
        </ul>
    </div>

    @if (! empty($documentStatusOptions))
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;align-items:center;">
            <span class="table-meta">Status kelengkapan yang tersedia:</span>
            @foreach ($documentStatusOptions as $item)
                <span class="badge">{{ $item }}</span>
            @endforeach
        </div>
    @endif
</div>
