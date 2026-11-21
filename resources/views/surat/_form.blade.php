@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>Data belum dapat disimpan.</strong>
        <span>Periksa kembali field yang ditandai di bawah.</span>
    </div>
@endif

<div class="form-status-panel">
    <span>
        <small>Status Proses</small>
        <span class="status-badge status-{{ $surat->exists ? $surat->status : \App\Models\Surat::STATUS_BELUM_DITANGANI }}">
            {{ $surat->exists ? $surat->status_label : \App\Models\Surat::STATUS_LABELS[\App\Models\Surat::STATUS_BELUM_DITANGANI] }}
        </span>
    </span>
    <p>{{ $surat->exists ? 'Status proses diperbarui oleh pegawai yang menangani.' : 'Status awal surat otomatis Belum Ditangani.' }}</p>
</div>

<div class="form-grid">
    <div class="form-field">
        <label for="nomor_surat">Nomor Surat <span class="required-mark" aria-hidden="true">*</span></label>
        <input id="nomor_surat" name="nomor_surat" type="text" value="{{ old('nomor_surat', $surat->nomor_surat) }}" maxlength="255" required autofocus autocomplete="off" spellcheck="false" aria-describedby="nomor-surat-help{{ $errors->has('nomor_surat') ? ' nomor-surat-error' : '' }}" @class(['is-invalid' => $errors->has('nomor_surat')]) @if($errors->has('nomor_surat')) aria-invalid="true" @endif>
        <span class="field-help" id="nomor-surat-help">Nomor surat wajib unik dan tidak boleh sama dengan surat yang sudah tersimpan.</span>
        @error('nomor_surat')<span class="field-error" id="nomor-surat-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field">
        <label for="tanggal_masuk">Tanggal Masuk <span class="required-mark" aria-hidden="true">*</span></label>
        <input id="tanggal_masuk" name="tanggal_masuk" type="date" value="{{ old('tanggal_masuk', $surat->tanggal_masuk?->format('Y-m-d')) }}" required data-field-click-feedback data-open-date-picker @class(['is-invalid' => $errors->has('tanggal_masuk')]) @if($errors->has('tanggal_masuk')) aria-invalid="true" aria-describedby="tanggal-masuk-error" @endif>
        @error('tanggal_masuk')<span class="field-error" id="tanggal-masuk-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field">
        <label for="pemohon_pengirim">Pemohon / Pengirim <span class="required-mark" aria-hidden="true">*</span></label>
        <input id="pemohon_pengirim" name="pemohon_pengirim" type="text" value="{{ old('pemohon_pengirim', $surat->pemohon_pengirim) }}" maxlength="255" required @class(['is-invalid' => $errors->has('pemohon_pengirim')]) @if($errors->has('pemohon_pengirim')) aria-invalid="true" aria-describedby="pemohon-error" @endif>
        @error('pemohon_pengirim')<span class="field-error" id="pemohon-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field">
        <label for="kategori_surat_id">Kategori Surat <span class="required-mark" aria-hidden="true">*</span></label>
        <select id="kategori_surat_id" name="kategori_surat_id" required data-field-click-feedback @class(['is-invalid' => $errors->has('kategori_surat_id')]) @if($errors->has('kategori_surat_id')) aria-invalid="true" aria-describedby="kategori-help kategori-error" @else aria-describedby="kategori-help" @endif>
            <option value="" disabled @selected(old('kategori_surat_id', $surat->kategori_surat_id) === null || old('kategori_surat_id', $surat->kategori_surat_id) === '')>— Pilih Kategori Surat —</option>
            @foreach ($kategoriSurats as $kategori)
                <option value="{{ $kategori->id }}" @selected((string) old('kategori_surat_id', $surat->kategori_surat_id) === (string) $kategori->id)>{{ $kategori->nama }}</option>
            @endforeach
        </select>
        <span class="field-help" id="kategori-help">Pilih Surat Lain-lain apabila surat tidak termasuk lima kategori utama.</span>
        @error('kategori_surat_id')<span class="field-error" id="kategori-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field form-field-full">
        <label for="keterangan">Keterangan <span class="optional-field">(Opsional)</span></label>
        <textarea id="keterangan" name="keterangan" rows="3" maxlength="10000" placeholder="Tambahkan keterangan mengenai berkas atau informasi lain jika diperlukan" @class(['is-invalid' => $errors->has('keterangan')]) aria-describedby="keterangan-help{{ $errors->has('keterangan') ? ' keterangan-error' : '' }}" @if($errors->has('keterangan')) aria-invalid="true" @endif>{{ old('keterangan', $surat->keterangan) }}</textarea>
        <span class="field-help" id="keterangan-help">Informasi tambahan terkait berkas atau penanganan awal surat.</span>
        @error('keterangan')<span class="field-error" id="keterangan-error">{{ $message }}</span>@enderror
    </div>
</div>

<div class="form-actions">
    <button class="primary-button" type="submit" data-loading-label="{{ $loadingLabel }}">{{ $submitLabel }}</button>
    <a class="outline-button" href="{{ $cancelUrl }}">Batal / Kembali</a>
</div>
