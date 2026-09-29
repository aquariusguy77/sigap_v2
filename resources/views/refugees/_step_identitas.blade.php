<div class="wizard-panel active" data-step-panel="1" style="margin-top:18px;">
    <div class="double-grid" style="margin-top:0;">
        <div>
            <label class="table-meta">ID Internal</label>
            <input class="control" type="text" name="internal_id" value="{{ old('internal_id', $refugee->internal_id ?? '') }}" placeholder="RDS-24031" required>
            @error('internal_id')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="table-meta">Nama Lengkap</label>
            <input class="control" type="text" name="name" value="{{ old('name', $refugee->name ?? '') }}" placeholder="Nama pengungsi" required>
            @error('name')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="table-meta">Kebangsaan</label>
            {{--
                Kolom ketik, bukan daftar pilih.

                Sebelumnya ini berupa <select> yang isinya dikumpulkan dari
                kebangsaan yang sudah ada di data. Akibatnya pengungsi dari
                negara yang belum pernah didata tidak dapat dimasukkan sama
                sekali — dan pada basis data yang masih kosong, daftarnya pun
                kosong sehingga tidak ada satu pun pilihan.

                Daftar lama tetap disediakan lewat <datalist> sebagai saran,
                agar penulisan nama negara yang sudah ada tetap seragam.
            --}}
            <input class="control" type="text" name="nationality" list="daftarKebangsaan"
                   value="{{ old('nationality', $refugee->nationality ?? '') }}"
                   placeholder="Ketik atau pilih, contoh: Afghanistan"
                   maxlength="100" autocomplete="off" required>
            <datalist id="daftarKebangsaan">
                @foreach ($nationalityOptions as $item)
                    <option value="{{ $item }}"></option>
                @endforeach
            </datalist>
            <div class="table-meta" style="margin-top:6px;">
                Kebangsaan baru boleh langsung diketik; saran di bawah kolom hanya membantu agar penulisannya seragam.
            </div>
            @error('nationality')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="table-meta">Nomor UNHCR</label>
            <input class="control" type="text" name="unhcr_number" value="{{ old('unhcr_number', $refugee->unhcr_number ?? '') }}" placeholder="UNHCR-XXX-0000">
            @error('unhcr_number')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="table-meta">Nomor Telepon <span style="opacity:.7;">(opsional)</span></label>
            <input class="control" type="tel" name="phone" inputmode="tel"
                   value="{{ old('phone', $refugee->phone ?? '') }}"
                   placeholder="0812-3456-7890" maxlength="25">
            <div class="table-meta" style="margin-top:6px;">
                Nomor yang dapat dihubungi petugas saat pengawasan lapangan. Boleh dikosongkan bila memang tidak ada.
            </div>
            @error('phone')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
