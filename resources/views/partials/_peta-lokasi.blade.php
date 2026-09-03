{{--
    Panel lokasi untuk pengungsi mandiri.

    Dipakai bersama oleh halaman detail penempatan dan detail pengungsi.
    Variabel yang diharapkan:
      $peta        catatan penempatan (Record atau array)
      $judul       judul panel, opsional
--}}
@php
    $petaEmbed = $peta['embed_url'] ?? null;
    $petaArah = $peta['directions_url'] ?? null;
    $petaLihat = $peta['map_url'] ?? null;
    $petaAlamat = $peta['address'] ?? null;
    $petaLintang = $peta['latitude'] ?? null;
    $petaBujur = $peta['longitude'] ?? null;
@endphp

@if (filled($petaArah))
    <section class="panel" style="margin-top:14px;">
        <div class="section-head">
            <div>
                <span class="section-tag"><x-icon name="location" class="chip-icon" />Pengawasan Lapangan</span>
                <h3>{{ $judul ?? 'Menuju tempat tinggal pengungsi' }}</h3>
                <p class="section-intro">
                    Pengungsi mandiri tinggal menyebar, tidak terpusat seperti di Community House.
                    Ketuk petanya untuk membuka rute di Google Maps.
                </p>
            </div>
        </div>

        @if (filled($petaAlamat))
            <p style="margin:0 0 14px;">{{ $petaAlamat }}</p>
        @endif

        @if (filled($petaEmbed))
            {{--
                Peta sematan dilapisi tautan tembus pandang supaya seluruh
                bidang peta dapat diketuk. Iframe menelan klik, jadi tautannya
                harus berada di atasnya, bukan di dalamnya.
            --}}
            <div style="position:relative;border-radius:12px;overflow:hidden;border:1px solid var(--line);">
                <iframe
                    src="{{ $petaEmbed }}"
                    title="Peta lokasi tempat tinggal"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    style="width:100%;height:260px;border:0;display:block;"></iframe>
                <a href="{{ $petaArah }}" target="_blank" rel="noopener"
                   aria-label="Buka petunjuk arah di Google Maps"
                   style="position:absolute;inset:0;display:block;"></a>
            </div>
            <p class="table-meta" style="margin-top:8px;">
                Titik pada peta: {{ $petaLintang }}, {{ $petaBujur }} &bull; peta dari OpenStreetMap, navigasi memakai Google Maps.
            </p>
        @else
            <div class="subtle-box" style="margin-top:0;">
                <h4>Peta belum tersedia</h4>
                <p>
                    Titik koordinat rumah belum ditetapkan, sehingga peta kecilnya belum bisa
                    ditampilkan. Tombol di bawah tetap dapat dipakai — Google Maps akan
                    mencarinya berdasarkan alamat, dengan ketelitian bergantung pada
                    kelengkapan alamat tersebut.
                </p>
                <p>Tetapkan titiknya lewat menu Ubah Penempatan agar peta muncul di sini.</p>
            </div>
        @endif

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:14px;">
            <a class="btn btn-primary" href="{{ $petaArah }}" target="_blank" rel="noopener">
                <x-icon name="location" class="chip-icon" /> Petunjuk Arah
            </a>
            <a class="btn btn-ghost" href="{{ $petaLihat }}" target="_blank" rel="noopener">
                <x-icon name="search" class="chip-icon" /> Lihat di Google Maps
            </a>
        </div>
    </section>
@endif
