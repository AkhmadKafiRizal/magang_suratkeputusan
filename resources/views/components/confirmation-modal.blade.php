<div class="confirmation-modal" data-confirmation-modal hidden>
    <button class="confirmation-backdrop" type="button" data-confirmation-close aria-label="Tutup dialog konfirmasi"></button>
    <section
        class="confirmation-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirmation-title"
        aria-describedby="confirmation-message"
        tabindex="-1"
    >
        <button class="confirmation-close" type="button" data-confirmation-close aria-label="Tutup dialog">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17" stroke-linecap="round"/></svg>
        </button>
        <span class="confirmation-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M12 9v4M12 17h.01" stroke-linecap="round"/><path d="M10.3 4.2 2.7 17.4A1.7 1.7 0 0 0 4.2 20h15.6a1.7 1.7 0 0 0 1.5-2.6L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke-linejoin="round"/></svg>
        </span>
        <div class="confirmation-copy">
            <h2 id="confirmation-title" data-confirmation-title>Konfirmasi Tindakan</h2>
            <p id="confirmation-message" data-confirmation-message>Pastikan Anda ingin melanjutkan tindakan ini.</p>
        </div>
        <div class="confirmation-actions">
            <button class="outline-button" type="button" data-confirmation-close data-confirmation-cancel>Batal</button>
            <button class="primary-button" type="button" data-confirmation-submit>Ya, Lanjutkan</button>
        </div>
    </section>
</div>
