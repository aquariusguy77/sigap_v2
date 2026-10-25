@php
    $selectedRefugee = old('refugee_id', $placement->refugee_id ?? '');
    $selectedStatus = old('placement_status', $placement->placement_status ?? '');
    $selectedCategory = old('category', $placement->category ?? 'iom');
    $selectedHouse = old('community_house', $placement->community_house ?? '');
@endphp

<form method="POST" action="{{ $formAction }}" novalidate>
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
                <label class="table-meta" for="pilihPengungsiPenempatan">Pengungsi</label>
                <select class="control" id="pilihPengungsiPenempatan" name="refugee_id" required>
                    <option value="">Pilih pengungsi</option>
                    @foreach ($refugees as $item)
                        <option value="{{ $item['id'] }}" @selected((string) $selectedRefugee === (string) $item['id'])>{{ $item['name'] }} - {{ $item['internal_id'] }}</option>
                    @endforeach
                </select>
                @error('refugee_id')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta" for="placementCategory">Kategori Penempatan</label>
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
            <label class="table-meta" for="communityHouse">Community House</label>
            <select class="control" name="community_house" id="communityHouse">
                <option value="">Pilih Community House</option>
                @foreach ($communityHouses as $house)
                    <option value="{{ $house }}" @selected($selectedHouse === $house)>{{ $house }}</option>
                @endforeach
            </select>
            @error('community_house')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>

        {{-- Hanya untuk pengungsi mandiri --}}
        @include('partials._pemilih-titik', [
            'alamat' => $placement->address ?? '',
            'lintang' => $placement->latitude ?? '',
            'bujur' => $placement->longitude ?? '',
        ])

        <div class="double-grid" style="margin-top:18px;">
            <div>
                <label class="table-meta" for="kolom-entered-at">Tanggal Masuk</label>
                <input id="kolom-entered-at" class="control" type="date" name="entered_at" value="{{ old('entered_at', optional($placement->entered_at ?? null)->format('Y-m-d')) }}" required>
                @error('entered_at')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta" for="kolom-exited-at">Tanggal Keluar</label>
                <input id="kolom-exited-at" class="control" type="date" name="exited_at" value="{{ old('exited_at', optional($placement->exited_at ?? null)->format('Y-m-d')) }}">
                @error('exited_at')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="table-meta" for="kolom-placement-status">Status</label>
                <select id="kolom-placement-status" class="control" name="placement_status" required>
                    <option value="">Pilih status</option>
                    @foreach ($statusOptions as $item)
                        <option value="{{ $item }}" @selected($selectedStatus === $item)>{{ $item }}</option>
                    @endforeach
                </select>
                @error('placement_status')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
            </div>
        </div>

        <div style="margin-top:18px;">
            <label class="table-meta" for="kolom-notes">Catatan</label>
            <textarea id="kolom-notes" class="control" name="notes" rows="4" style="width:100%;resize:vertical;" maxlength="1000">{{ old('notes', $placement->notes ?? '') }}</textarea>
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

        select?.addEventListener('change', () => {
            apply();
            window.__sigapUkurUlangPeta?.();
        });
        apply();
    })();
</script>
