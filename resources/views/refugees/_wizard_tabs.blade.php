@php
    $langkahBergalat = $langkahBergalat ?? [];
    $tandaGalat = '<span class="table-meta" style="display:block;margin-top:6px;color:var(--danger);font-weight:600;">Perlu diperbaiki</span>';
@endphp
<div class="step-grid" style="margin-top:0;">
    <button type="button" class="step-card active wizard-tab" data-step="1">
        <span class="step-index">1</span><strong>Identitas</strong>
        <p>Nama, ID internal, kebangsaan, dan nomor UNHCR.</p>
        @if (in_array(1, $langkahBergalat, true)){!! $tandaGalat !!}@endif
    </button>
    <button type="button" class="step-card wizard-tab" data-step="2">
        <span class="step-index">2</span><strong>Administrasi</strong>
        <p>Status data dan tanggal registrasi.</p>
        @if (in_array(2, $langkahBergalat, true)){!! $tandaGalat !!}@endif
    </button>
    <button type="button" class="step-card wizard-tab" data-step="3">
        <span class="step-index">3</span><strong>Penempatan</strong>
        <p>Lokasi hunian aktif saat ini.</p>
        @if (in_array(3, $langkahBergalat, true)){!! $tandaGalat !!}@endif
    </button>
    <button type="button" class="step-card wizard-tab" data-step="4">
        <span class="step-index">4</span><strong>Dokumen</strong>
        <p>Kelengkapan berkas pendukung.</p>
        @if (in_array(4, $langkahBergalat, true)){!! $tandaGalat !!}@endif
    </button>
</div>
