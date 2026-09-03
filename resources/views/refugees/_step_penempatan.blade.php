<div class="wizard-panel" data-step-panel="3" style="margin-top:18px;display:none;">
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

    {{--
        Kolom "Rencana Mutasi" yang dulu ada di sini sudah dihapus. Kolom itu
        selalu disabled dan tidak pernah ikut tersimpan, sehingga apa pun yang
        diketik petugas hilang tanpa pemberitahuan.
    --}}
    <div class="subtle-box">
        <h4>Keterangan lokasi</h4>
        <ul>
            <li><strong>CH Puspa Agro</strong> dan <strong>CH Green Bamboo</strong> untuk pengungsi yang memperoleh fasilitas dari IOM.</li>
            <li><strong>Pengungsi Mandiri</strong> untuk yang mencari tempat tinggal sendiri.</li>
            <li>Alamat rinci, koordinat, dan petunjuk arah bagi pengungsi mandiri dicatat lewat menu <strong>Penempatan</strong> setelah data ini tersimpan.</li>
        </ul>
    </div>
</div>
