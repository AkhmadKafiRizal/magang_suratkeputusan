<div class="detail-sections">
    <section class="detail-section" aria-labelledby="informasi-surat-title">
        <div class="detail-section-heading">
            <span class="detail-section-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h9l4 4v14H6z" stroke-linejoin="round"/><path d="M14 3v5h5M9 12h7M9 16h7" stroke-linecap="round"/></svg>
            </span>
            <h4 id="informasi-surat-title">Informasi Surat</h4>
        </div>
        <dl class="detail-section-grid">
            <div><dt>Tanggal Masuk</dt><dd>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d F Y') }}</dd></div>
            <div><dt>Pemohon / Pengirim</dt><dd>{{ $surat->pemohon_pengirim }}</dd></div>
            <div class="detail-item-full"><dt>Perihal</dt><dd>{{ $surat->perihal }}</dd></div>
        </dl>
    </section>

    <section class="detail-section" aria-labelledby="penanganan-surat-title">
        <div class="detail-section-heading">
            <span class="detail-section-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 3-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <h4 id="penanganan-surat-title">Penanganan</h4>
        </div>
        <dl class="detail-section-grid">
            <div><dt>Pegawai yang Menangani</dt><dd class="detail-value-emphasis">{{ $surat->pegawai?->name ?? 'Belum Ditugaskan' }}</dd></div>
            <div><dt>Status</dt><dd><span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span></dd></div>
            <div class="detail-item-full"><dt>Catatan Administratif</dt><dd class="preserve-lines">{{ $surat->keterangan ?: 'Tidak ada catatan administratif' }}</dd></div>
        </dl>
    </section>

    <section class="detail-section" aria-labelledby="informasi-waktu-title">
        <div class="detail-section-heading">
            <span class="detail-section-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <h4 id="informasi-waktu-title">Informasi Waktu</h4>
        </div>
        <dl class="detail-section-grid detail-time-grid">
            <div><dt>Dicatat di Sistem</dt><dd>{{ $surat->created_at->locale('id')->translatedFormat('d F Y, H:i') }} WIB</dd></div>
            <div><dt>Ditugaskan kepada Pegawai</dt><dd>{{ $surat->ditugaskan_pada ? $surat->ditugaskan_pada->locale('id')->translatedFormat('d F Y, H:i').' WIB' : 'Belum Ditugaskan' }}</dd></div>
            <div><dt>Mulai Diproses</dt><dd>{{ $surat->mulai_diproses_pada ? $surat->mulai_diproses_pada->locale('id')->translatedFormat('d F Y, H:i').' WIB' : ($surat->status === \App\Models\Surat::STATUS_BELUM_DITANGANI ? 'Belum Diproses' : 'Belum Tercatat') }}</dd></div>
            <div><dt>Selesai Diproses</dt><dd>{{ $surat->selesai_pada ? $surat->selesai_pada->locale('id')->translatedFormat('d F Y, H:i').' WIB' : 'Belum Selesai' }}</dd></div>
            <div><dt>Terakhir Diperbarui</dt><dd>{{ $surat->updated_at->locale('id')->translatedFormat('d F Y, H:i') }} WIB</dd></div>
        </dl>
    </section>
</div>
