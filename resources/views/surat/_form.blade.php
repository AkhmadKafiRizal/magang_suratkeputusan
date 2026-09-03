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
        <label for="nomor_surat">Nomor Surat <span aria-hidden="true">*</span></label>
        <input id="nomor_surat" name="nomor_surat" type="text" value="{{ old('nomor_surat', $surat->nomor_surat) }}" maxlength="255" required autofocus @class(['is-invalid' => $errors->has('nomor_surat')])>
        @error('nomor_surat')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field">
        <label for="tanggal_masuk">Tanggal Masuk <span aria-hidden="true">*</span></label>
        <input id="tanggal_masuk" name="tanggal_masuk" type="date" value="{{ old('tanggal_masuk', $surat->tanggal_masuk?->format('Y-m-d')) }}" required @class(['is-invalid' => $errors->has('tanggal_masuk')])>
        @error('tanggal_masuk')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field form-field-full">
        <label for="perihal">Perihal <span aria-hidden="true">*</span></label>
        <textarea id="perihal" name="perihal" rows="2" maxlength="2000" required @class(['is-invalid' => $errors->has('perihal')])>{{ old('perihal', $surat->perihal) }}</textarea>
        @error('perihal')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field">
        <label for="pemohon_pengirim">Pemohon / Pengirim <span aria-hidden="true">*</span></label>
        <input id="pemohon_pengirim" name="pemohon_pengirim" type="text" value="{{ old('pemohon_pengirim', $surat->pemohon_pengirim) }}" maxlength="255" required @class(['is-invalid' => $errors->has('pemohon_pengirim')])>
        @error('pemohon_pengirim')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field">
        <label for="pegawai_id">Pegawai yang Menangani</label>
        <select id="pegawai_id" name="pegawai_id" @class(['is-invalid' => $errors->has('pegawai_id')])>
            <option value="">Belum Ditugaskan</option>
            @foreach ($pegawai as $anggota)
                <option value="{{ $anggota->id }}" @selected((string) old('pegawai_id', $surat->pegawai_id) === (string) $anggota->id)>{{ $anggota->name }} — {{ $anggota->email }}</option>
            @endforeach
        </select>
        @error('pegawai_id')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-field form-field-full">
        <label for="keterangan">Catatan Administratif</label>
        <textarea id="keterangan" name="keterangan" rows="3" maxlength="10000" placeholder="Tambahkan catatan mengenai berkas atau informasi administratif jika diperlukan" @class(['is-invalid' => $errors->has('keterangan')])>{{ old('keterangan', $surat->keterangan) }}</textarea>
        <span class="field-help">Catatan tambahan terkait berkas atau penanganan awal surat (opsional).</span>
        @error('keterangan')<span class="field-error">{{ $message }}</span>@enderror
    </div>
</div>

<div class="form-actions">
    <button class="primary-button" type="submit">{{ $submitLabel }}</button>
    <a class="outline-button" href="{{ $cancelUrl }}">Batal / Kembali</a>
</div>
