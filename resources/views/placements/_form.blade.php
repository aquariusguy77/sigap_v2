@php
    $selectedRefugee = old('refugee_id', $placement->refugee_id ?? '');
    $selectedStatus = old('placement_status', $placement->placement_status ?? '');
    $selectedCategory = old('category', $placement->category ?? 'iom');
    $selectedHouse = old('community_house', $placement->community_house ?? '');
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
                <h3>Kategori, lokasi, dan status penempatan</h3>
                <p class="section-intro">Pengungsi berfasilitas IOM ditempatkan di Community House. Pengungsi mandiri mencari tempat tinggal sendiri, sehingga alamatnya perlu dicatat lengkap.</p>
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
                <label class="table-meta">Kategori Penempatan</label>
                <select class="control" name="category" id="placementCategory" required>
                    @foreach ($categoryOptions as $key => $option)
                        <option value="{{ $key }}" @selected($selectedCategory === $key)>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <div class="table-meta" id="categoryHint" style="margin-top:6px;"></div>
                @error('category')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Hanya untuk pengungsi berfasilitas IOM --}}
        <div class="iom-only-field" style="margin-top:18px;">
            <label class="table-meta">Community House</label>
            <select class="control" name="community_house" id="communityHouse">
                <option value="">Pilih Community House</option>
                @foreach ($communityHouses as $house)
                    <option value="{{ $house }}" @selected($selectedHouse === $house)>{{ $house }}</option>
                @endforeach
            </select>
            @error('community_house')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>

        {{-- Hanya untuk pengungsi mandiri --}}
        <div class="mandiri-only-field" style="margin-top:18px;">
            <label class="table-meta">Alamat Tempat Tinggal</label>
            <textarea class="control" name="address" id="placementAddress" rows="3" style="width:100%;resize:vertical;" maxlength="500" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota">{{ old('address', $placement->address ?? '') }}</textarea>
            <div class="table-meta" style="margin-top:6px;">Tulis selengkap mungkin. Alamat inilah yang dipakai petugas untuk menemukan rumahnya saat pengawasan lapangan.</div>
            @error('address')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>

        <div class="mandiri-only-field" style="margin-top:18px;">
            <label class="table-meta">Titik Lokasi <span style="opacity:.7;">(opsional)</span></label>
            <input class="control" type="text" id="coordinatePaste" name="coordinate_paste"
                   value="{{ old('coordinate_paste') }}"
                   placeholder="Tempel di sini: -7.348123, 112.727456 atau tautan Google Maps">
            <div class="table-meta" style="margin-top:6px;">
                Buka Google Maps di titik rumah pengungsi, tekan lama pada titiknya, lalu salin
                koordinat yang muncul dan tempel di kolom ini. Lintang dan bujur akan terisi sendiri.
            </div>
            @error('coordinate_paste')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>

        {{--
            Peta pemilih titik. Hanya muncul bila pustaka petanya berhasil
            dimuat; tanpa itu, kolom tempel dan kedua kolom angka di bawah
            tetap dapat dipakai seperti biasa.
        --}}
        <div class="mandiri-only-field" id="pickerWrap" style="margin-top:14px;display:none;">
            <div id="pickerMap" style="height:280px;border-radius:12px;border:1px solid var(--line);"></div>
            <div class="table-meta" style="margin-top:6px;">
                Ketuk peta untuk menetapkan titik rumah, atau geser penandanya. Gulir untuk memperbesar.
            </div>
        </div>

        <div class="double-grid mandiri-only-field" style="margin-top:14px;">
            <div>
                <label class="table-meta">Lintang</label>
                <input class="control" type="text" name="latitude" id="latitudeInput" inputmode="decimal" value="{{ old('latitude', $placement->latitude ?? '') }}" placeholder="-7.348123">
                @error('latitude')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta">Bujur</label>
                <input class="control" type="text" name="longitude" id="longitudeInput" inputmode="decimal" value="{{ old('longitude', $placement->longitude ?? '') }}" placeholder="112.727456">
                @error('longitude')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="subtle-box mandiri-only-field" style="margin-top:14px;">
            <h4>Kalau titiknya dikosongkan</h4>
            <ul>
                <li>Data tetap tersimpan, dan tombol petunjuk arah tetap dapat dipakai.</li>
                <li>Google Maps akan mencarinya dari alamat, sehingga ketelitiannya bergantung pada kelengkapan alamat.</li>
                <li>Peta kecil pada halaman detail hanya muncul bila titiknya sudah ditetapkan.</li>
            </ul>
        </div>

        <div class="double-grid" style="margin-top:18px;">
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

<script>
    (() => {
        const select = document.getElementById('placementCategory');
        const hint = document.getElementById('categoryHint');
        const house = document.getElementById('communityHouse');
        const address = document.getElementById('placementAddress');
        const iomFields = document.querySelectorAll('.iom-only-field');
        const mandiriFields = document.querySelectorAll('.mandiri-only-field');

        const descriptions = @json($categoryOptions->map(fn ($item) => $item['description'] ?? '')->all());

        const apply = () => {
            const isMandiri = (select?.value || 'iom') === 'mandiri';

            iomFields.forEach((el) => { el.hidden = isMandiri; });
            mandiriFields.forEach((el) => { el.hidden = !isMandiri; });

            // Kolom yang tersembunyi tidak boleh ikut diwajibkan browser.
            if (house) house.required = !isMandiri;
            if (address) address.required = isMandiri;
            if (hint) hint.textContent = descriptions[select?.value] || '';
        };

        select?.addEventListener('change', apply);
        apply();

        // ---- Kolom tempel koordinat -------------------------------------
        const paste = document.getElementById('coordinatePaste');
        const latInput = document.getElementById('latitudeInput');
        const lngInput = document.getElementById('longitudeInput');

        /*
         * Pola yang sama dengan yang dipakai di sisi server, supaya hasilnya
         * konsisten baik JavaScript aktif maupun tidak.
         */
        const bacaKoordinat = (teks) => {
            const t = (teks || '').trim();
            if (!t) return null;
            const url = t.match(/@(-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)/);
            if (url) return [url[1], url[2]];
            const pasangan = t.match(/(-?\d{1,3}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
            return pasangan ? [pasangan[1], pasangan[2]] : null;
        };

        const terapkanTempelan = () => {
            const titik = bacaKoordinat(paste?.value);
            if (!titik) return null;
            if (latInput) latInput.value = titik[0];
            if (lngInput) lngInput.value = titik[1];
            return titik;
        };

        paste?.addEventListener('input', () => {
            const titik = terapkanTempelan();
            if (titik && window.__sigapPindahkanPenanda) {
                window.__sigapPindahkanPenanda(parseFloat(titik[0]), parseFloat(titik[1]));
            }
        });
    })();
</script>

{{--
    Pemilih titik di atas peta. Pustaka Leaflet dimuat dari CDN dan peta
    dasarnya dari OpenStreetMap, keduanya tanpa kunci API maupun biaya.

    Seluruh blok ini bersifat tambahan. Bila CDN tidak dapat dijangkau,
    petanya tidak ditampilkan sama sekali dan pengisian koordinat tetap
    berjalan lewat kolom tempel dan kedua kolom angka.
--}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    (() => {
        if (typeof L === 'undefined') {
            return;
        }

        const wrap = document.getElementById('pickerWrap');
        const el = document.getElementById('pickerMap');
        const latInput = document.getElementById('latitudeInput');
        const lngInput = document.getElementById('longitudeInput');

        if (!wrap || !el || !latInput || !lngInput) {
            return;
        }

        wrap.style.display = '';

        // Titik awal: koordinat yang sudah tersimpan, atau pusat Kota Surabaya.
        const awalLat = parseFloat(latInput.value) || -7.2575;
        const awalLng = parseFloat(lngInput.value) || 112.7521;
        const sudahAda = !!(parseFloat(latInput.value) && parseFloat(lngInput.value));

        const map = L.map(el).setView([awalLat, awalLng], sudahAda ? 17 : 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; Kontributor OpenStreetMap',
        }).addTo(map);

        let marker = null;

        const tulisKolom = (lat, lng) => {
            latInput.value = lat.toFixed(6);
            lngInput.value = lng.toFixed(6);
        };

        const pindahkan = (lat, lng, geser = true) => {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', () => {
                    const p = marker.getLatLng();
                    tulisKolom(p.lat, p.lng);
                });
            }
            tulisKolom(lat, lng);
            if (geser) map.setView([lat, lng], Math.max(map.getZoom(), 17));
        };

        if (sudahAda) {
            pindahkan(awalLat, awalLng, false);
        }

        map.on('click', (e) => pindahkan(e.latlng.lat, e.latlng.lng, false));

        // Dipakai kolom tempel agar penanda ikut berpindah.
        window.__sigapPindahkanPenanda = (lat, lng) => {
            if (!isNaN(lat) && !isNaN(lng)) pindahkan(lat, lng);
        };

        /*
         * Peta yang dibangun saat panelnya masih tersembunyi akan salah ukur.
         * Ukuran dihitung ulang begitu panel mandiri benar-benar terlihat.
         */
        const kategori = document.getElementById('placementCategory');
        const hitungUlang = () => setTimeout(() => map.invalidateSize(), 60);
        kategori?.addEventListener('change', hitungUlang);
        document.querySelectorAll('[data-step-target]').forEach((tombol) => {
            tombol.addEventListener('click', hitungUlang);
        });
        hitungUlang();
    })();
</script>
