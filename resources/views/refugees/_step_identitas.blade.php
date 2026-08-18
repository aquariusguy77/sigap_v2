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
            <select class="control" name="nationality" required>
                <option value="">Pilih kebangsaan</option>
                @foreach ($nationalityOptions as $item)
                    <option value="{{ $item }}" @selected($selectedNationality === $item)>{{ $item }}</option>
                @endforeach
            </select>
            @error('nationality')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="table-meta">Nomor UNHCR</label>
            <input class="control" type="text" name="unhcr_number" value="{{ old('unhcr_number', $refugee->unhcr_number ?? '') }}" placeholder="UNHCR-XXX-0000">
            @error('unhcr_number')<div class="table-meta" style="color:var(--danger);margin-top:6px;">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
