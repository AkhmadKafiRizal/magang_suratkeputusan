@if (session('bulk_result'))
    @php($result = session('bulk_result'))
    <div class="bulk-result-modal" data-bulk-result-modal data-timeout="20000" role="presentation">
        <div class="bulk-result-backdrop" aria-hidden="true"></div>
        <section class="bulk-result-panel bulk-result-{{ $result['variant'] }}" role="dialog" aria-modal="true" aria-labelledby="bulk-result-title" aria-describedby="bulk-result-summary" tabindex="-1">
            <button class="modal-close-button" type="button" data-bulk-result-close aria-label="Tutup ringkasan">&times;</button>
            <div class="bulk-result-heading">
                <span class="bulk-result-icon" aria-hidden="true">
                    @if ($result['variant'] === 'success')
                        <svg viewBox="0 0 24 24"><path d="m6.5 12.5 3.2 3.2 7.8-8"/></svg>
                    @elseif ($result['variant'] === 'warning')
                        <svg viewBox="0 0 24 24"><path d="M12 7.5v5.2M12 16.6h.01"/><circle cx="12" cy="12" r="9"/></svg>
                    @else
                        <svg viewBox="0 0 24 24"><path d="m8 8 8 8M16 8l-8 8"/><circle cx="12" cy="12" r="9"/></svg>
                    @endif
                </span>
                <div class="bulk-result-title-copy">
                    <span class="section-kicker">Ringkasan Aksi Massal</span>
                    <h2 id="bulk-result-title">{{ $result['title'] }}</h2>
                    <p id="bulk-result-summary">Hasil pemrosesan surat terpilih telah dirangkum di bawah ini.</p>
                </div>
            </div>
            <div class="bulk-result-stats">
                <div class="bulk-result-stat bulk-result-stat-success"><strong>{{ $result['success_count'] }}</strong><span>{{ $result['success_label'] }}</span></div>
                <div class="bulk-result-stat bulk-result-stat-failure"><strong>{{ $result['failure_count'] }}</strong><span>{{ $result['failure_label'] }}</span></div>
            </div>
            @if ($result['reasons'] !== [])
                <div class="bulk-result-reasons"><strong>Perlu diperhatikan</strong><ul>@foreach ($result['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul></div>
            @endif
        </section>
    </div>
@endif
