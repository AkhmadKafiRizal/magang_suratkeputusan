<div class="app-modal description-editor-modal" hidden data-description-modal aria-hidden="true" data-description-reopen-id="{{ old('editing_surat_id') }}" data-description-reopen-value="{{ old('keterangan') }}">
    <button class="app-modal-backdrop" type="button" tabindex="-1" data-description-close aria-label="Tutup editor Keterangan"></button>
    <section class="app-modal-panel description-editor-panel" role="dialog" aria-modal="true" aria-labelledby="description-modal-title" tabindex="-1">
        <div class="app-modal-heading">
            <div class="description-modal-title"><span class="section-kicker">Edit Cepat</span><h2 id="description-modal-title">Edit Keterangan</h2><p data-description-number></p></div>
            <button class="modal-close-button" type="button" data-description-close aria-label="Tutup">&times;</button>
        </div>
        <form class="description-editor-form" method="POST" data-description-form data-loading-form>
            @csrf
            @method('PATCH')
            <input name="editing_surat_id" type="hidden" data-description-id>
            <div class="description-editor-body">
                <label for="quick-description">Keterangan</label>
                <div class="description-autocomplete" data-description-autocomplete data-suggestions-url="{{ route('surat.description-suggestions') }}">
                    <textarea id="quick-description" name="keterangan" rows="5" maxlength="10000" autocomplete="off" data-description-input aria-autocomplete="list" aria-expanded="false"></textarea>
                    <div class="description-suggestions" role="listbox" hidden data-description-suggestions></div>
                </div>
                @error('keterangan')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="modal-actions description-editor-actions"><button class="outline-button" type="button" data-description-close>Batal</button><button class="primary-button" type="submit" data-loading-label="Menyimpan...">Simpan Keterangan</button></div>
        </form>
    </section>
</div>
