<section class="content-card detail-history-card detail-card-contained" aria-labelledby="riwayat-surat-title">
    <div class="card-heading">
        <div>
            <span class="section-kicker">Jejak Pekerjaan</span>
            <h2 id="riwayat-surat-title">Riwayat Surat</h2>
            <p>Lima aktivitas terbaru dari seluruh penggunaan nomor surat {{ $surat->nomor_surat }}.</p>
        </div>
        @if ($aktivitasTerbaru->isNotEmpty())
            <a class="outline-button compact-button" href="{{ route('riwayat-aktivitas.index', ['surat' => $surat->id]) }}">Lihat Semua Riwayat</a>
        @endif
    </div>

    <div class="detail-history-list">
        @forelse ($aktivitasTerbaru as $aktivitas)
            <div class="detail-history-item">
                <div class="detail-history-leading">
                    <x-user-avatar :user="$aktivitas->actor" size="medium" :lazy="true" />
                    <div class="detail-history-time">
                        <time
                            datetime="{{ $aktivitas->created_at->toIso8601String() }}"
                            title="{{ $aktivitas->created_at->locale('id')->translatedFormat('d F Y, H:i') }} WIB"
                        >{{ $aktivitas->created_at->format('H:i') }} WIB</time>
                        <span>{{ $aktivitas->created_at->locale('id')->translatedFormat('d F Y') }}</span>
                    </div>
                </div>
                <div class="detail-history-copy">
                    <span>{{ $aktivitas->deskripsi_tampilan }}</span>
                </div>
            </div>
        @empty
            <div class="section-empty detail-history-empty">
                <strong>Belum ada riwayat surat</strong>
                <span>Aktivitas pencatatan, penghapusan, penggunaan ulang, dan progres surat akan tampil di sini.</span>
            </div>
        @endforelse
    </div>

</section>
