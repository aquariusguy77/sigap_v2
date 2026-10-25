<div class="wizard-panel" data-step-panel="3" style="margin-top:18px;display:none;">
    <div>
        <label class="table-meta" for="kolom-location">Lokasi Aktif</label>
        <select id="kolom-location" class="control" name="location" required>
            <option value="">Pilih lokasi</option>
            @foreach ($locationOptions as $item)
                <option value="{{ $item }}" @selected($selectedLocation === $item)>{{ $item }}</option>
            @endforeach
        </select>
        @error('location')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
    </div>

    {{--
        Kolom "Rencana Mutasi" yang dulu ada di sini sudah dihapus. Kolom itu
        selalu disabled dan tidak pernah ikut tersimpan, sehingga apa pun yang
        diketik petugas hilang tanpa pemberitahuan.

        Alamat dan titik peta pengungsi mandiri kini dicatat langsung di
        langkah ini, lalu disimpan sebagai data Penempatan bersamaan dengan
        data pengungsinya.
    --}}
    @include('partials._pemilih-titik', [
        'alamat' => $placement->address ?? '',
        'lintang' => $placement->latitude ?? '',
        'bujur' => $placement->longitude ?? '',
    ])

    <div class="subtle-box">
        <h4>Keterangan lokasi</h4>
        <ul>
            <li><strong>CH Puspa Agro</strong> dan <strong>CH Green Bamboo</strong> untuk pengungsi yang memperoleh fasilitas dari IOM.</li>
            <li><strong>Pengungsi Mandiri</strong> untuk yang mencari tempat tinggal sendiri. Alamat dan titik rumahnya diisi di langkah ini.</li>
            <li>Lokasi ini otomatis tercatat di menu <strong>Penempatan</strong>. Tanggal masuk mengikuti tanggal registrasi.</li>
        </ul>
    </div>
</div>

<script>
    (() => {
        const lokasi = document.getElementById('kolom-location');
        const alamat = document.getElementById('placementAddress');
        const kolomMandiri = document.querySelectorAll('[data-step-panel="3"] .mandiri-only-field');

        const terapkan = () => {
            const mandiri = lokasi?.value === @json(\App\Services\RefugeeRecordSync::LOKASI_MANDIRI);
            kolomMandiri.forEach((el) => { el.hidden = !mandiri; });
            if (alamat) alamat.required = mandiri;
            if (mandiri) window.__sigapUkurUlangPeta?.();
        };

        lokasi?.addEventListener('change', terapkan);
        terapkan();
    })();
</script>
