{{--
    Kolom tempat tinggal pengungsi mandiri: alamat, kolom tempel koordinat,
    peta pemilih titik, serta lintang dan bujur.

    Dipakai formulir Penempatan dan langkah Penempatan pada formulir Data
    Pengungsi. Menampilkan atau menyembunyikan blok ini diatur formulir
    pemakainya lewat kelas "mandiri-only-field"; setelah blok ini terlihat,
    formulir pemakai memanggil window.__sigapUkurUlangPeta() agar peta tidak
    salah ukur.

    Variabel: $alamat, $lintang, $bujur (nilai awal, boleh kosong).
--}}
<div class="mandiri-only-field" style="margin-top:18px;">
    <label class="table-meta" for="placementAddress">Alamat Tempat Tinggal</label>
    <textarea class="control" name="address" id="placementAddress" rows="3" style="width:100%;resize:vertical;" maxlength="500" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota">{{ old('address', $alamat ?? '') }}</textarea>
    <div class="table-meta" style="margin-top:6px;">Tulis selengkap mungkin. Alamat inilah yang dipakai petugas untuk menemukan rumahnya saat pengawasan lapangan.</div>
    @error('address')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
</div>

<div class="mandiri-only-field" style="margin-top:18px;">
    <label class="table-meta" for="coordinatePaste">Titik Lokasi <span style="opacity:.7;">(opsional)</span></label>
    <input class="control" type="text" id="coordinatePaste" name="coordinate_paste"
           value="{{ old('coordinate_paste') }}"
           placeholder="Tempel koordinat atau tautan Google Maps di sini">
    <div class="table-meta" id="coordinatePasteStatus" style="margin-top:6px;" aria-live="polite"></div>
    <div class="table-meta" style="margin-top:6px;">
        Ketuk titik rumahnya di peta di bawah, atau tempel tautan dari tombol <strong>Bagikan</strong>
        di Google Maps maupun angka koordinatnya. Pin akan berpindah ke titik tersebut.
    </div>
    @error('coordinate_paste')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
</div>

{{--
    Peta pemilih titik. Hanya muncul bila pustaka petanya berhasil dimuat;
    tanpa itu, kolom tempel dan kedua kolom angka di bawah tetap dapat dipakai.
--}}
<div class="mandiri-only-field" id="pickerWrap" style="margin-top:14px;display:none;">
    <div id="pickerMap" style="height:280px;border-radius:12px;border:1px solid var(--line);"></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:8px;">
        <button type="button" class="btn btn-ghost btn-sm" id="pickerHere">Pakai lokasi saya sekarang</button>
        <span class="table-meta">Ketuk peta untuk menetapkan titik rumah, atau geser penandanya.</span>
    </div>
</div>

<div class="double-grid mandiri-only-field" style="margin-top:14px;">
    <div>
        <label class="table-meta" for="latitudeInput">Lintang</label>
        <input class="control" type="text" name="latitude" id="latitudeInput" inputmode="decimal" value="{{ old('latitude', $lintang ?? '') }}" placeholder="-7.348123">
        @error('latitude')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="table-meta" for="longitudeInput">Bujur</label>
        <input class="control" type="text" name="longitude" id="longitudeInput" inputmode="decimal" value="{{ old('longitude', $bujur ?? '') }}" placeholder="112.727456">
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

<script>
    (() => {
        const paste = document.getElementById('coordinatePaste');
        const status = document.getElementById('coordinatePasteStatus');
        const latInput = document.getElementById('latitudeInput');
        const lngInput = document.getElementById('longitudeInput');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        /*
         * Pola yang sama dengan GoogleMapsLinkResolver di sisi server. Pada
         * tautan tempat, "!3d…!4d…" adalah letak pin, sedangkan "@…" hanya
         * pusat tampilan peta, jadi pin didahulukan.
         */
        const bacaKoordinat = (teks) => {
            let t = (teks || '').trim();
            if (!t) return null;
            try { t = decodeURIComponent(t); } catch (e) { /* biarkan apa adanya */ }
            const angka = '(-?\\d{1,3}\\.\\d+)';
            const pola = [
                new RegExp('!3d' + angka + '!4d' + angka),
                new RegExp('@' + angka + ',\\s*' + angka),
                new RegExp('[?&](?:q|query|ll|center|destination|daddr)=' + angka + ',\\s*\\+?' + angka),
                new RegExp(angka + '\\s*,\\s*' + angka),
            ];
            for (const p of pola) {
                const m = t.match(p);
                if (m) return [m[1], m[2]];
            }
            return null;
        };

        // Tautan bagikan dari HP tidak memuat angka; titiknya dibaca server.
        const tautanPendek = (teks) => /^https?:\/\/(maps\.app\.goo\.gl|goo\.gl|g\.co)\//i.test((teks || '').trim());

        const tulisStatus = (pesan, galat = false) => {
            if (!status) return;
            status.textContent = pesan;
            status.style.color = galat ? 'var(--danger)' : '';
        };

        const terapkan = (titik) => {
            if (latInput) latInput.value = titik[0];
            if (lngInput) lngInput.value = titik[1];
            window.__sigapPindahkanPenanda?.(parseFloat(titik[0]), parseFloat(titik[1]));
            tulisStatus('Pin dipindahkan ke ' + titik[0] + ', ' + titik[1] + '. Periksa letaknya di peta.');
        };

        let urutan = 0;

        const proses = async () => {
            const teks = paste.value;
            const nomor = ++urutan;

            if (!teks.trim()) {
                tulisStatus('');
                return;
            }

            const titik = bacaKoordinat(teks);
            if (titik) {
                terapkan(titik);
                return;
            }

            if (!tautanPendek(teks)) {
                tulisStatus('Titik lokasi tidak terbaca dari teks ini.', true);
                return;
            }

            tulisStatus('Membaca tautan Google Maps…');

            try {
                const res = await fetch(@json(route('maps.resolve')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf || '' },
                    body: JSON.stringify({ link: teks.trim() }),
                });
                const data = await res.json().catch(() => ({}));
                if (nomor !== urutan) return;

                if (res.ok && data.latitude && data.longitude) {
                    terapkan([String(data.latitude), String(data.longitude)]);
                } else {
                    tulisStatus(data.message || 'Titik lokasi tidak ditemukan pada tautan ini.', true);
                }
            } catch (e) {
                if (nomor === urutan) tulisStatus('Tautan belum bisa dibaca. Periksa sambungan internet, lalu tempel ulang.', true);
            }
        };

        let jeda = null;
        paste?.addEventListener('input', () => {
            clearTimeout(jeda);
            jeda = setTimeout(proses, 350);
        });
    })();
</script>

{{--
    Pustaka Leaflet dimuat dari CDN dan peta dasarnya dari OpenStreetMap,
    keduanya tanpa kunci API maupun biaya. Bila CDN tidak dapat dijangkau,
    petanya tidak ditampilkan dan pengisian koordinat tetap berjalan lewat
    kolom tempel dan kedua kolom angka.

    crossorigin="anonymous" membuat peramban mengambil berkas ini tanpa kuki,
    dan referrerpolicy="no-referrer" mencegah alamat halaman ini ikut terkirim
    ke pihak ketiga.
--}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"
      crossorigin="anonymous" referrerpolicy="no-referrer">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    (() => {
        window.__sigapUkurUlangPeta = () => {};

        if (typeof L === 'undefined') {
            return;
        }

        const wrap = document.getElementById('pickerWrap');
        const el = document.getElementById('pickerMap');
        const latInput = document.getElementById('latitudeInput');
        const lngInput = document.getElementById('longitudeInput');
        const here = document.getElementById('pickerHere');

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

        // Berguna saat petugas sedang berada di rumah pengungsi.
        if (here && 'geolocation' in navigator) {
            here.addEventListener('click', () => {
                here.disabled = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => { here.disabled = false; pindahkan(pos.coords.latitude, pos.coords.longitude); },
                    () => { here.disabled = false; alert('Lokasi tidak dapat dibaca. Izinkan akses lokasi di peramban, atau ketuk titiknya di peta.'); },
                    { enableHighAccuracy: true, timeout: 15000 }
                );
            });
        } else if (here) {
            here.hidden = true;
        }

        // Peta yang dibangun saat panelnya masih tersembunyi akan salah ukur.
        window.__sigapUkurUlangPeta = () => setTimeout(() => map.invalidateSize(), 60);
        window.__sigapUkurUlangPeta();
    })();
</script>
