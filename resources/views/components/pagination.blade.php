@if ($paginator->hasPages())
    <nav class="app-pagination" role="navigation" aria-label="Navigasi halaman">
        <p class="pagination-summary">
            Menampilkan <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            dari <strong>{{ $paginator->total() }}</strong> data
        </p>

        <div class="pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="pagination-button pagination-edge-button is-disabled" aria-disabled="true" title="Halaman pertama">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m11 7-5 5 5 5M18 7l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="pagination-label">Awal</span>
                </span>
                <span class="pagination-button pagination-edge-button is-disabled" aria-disabled="true" title="Halaman sebelumnya">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 6-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="pagination-label">Sebelumnya</span>
                </span>
            @else
                <a class="pagination-button pagination-edge-button" href="{{ $paginator->url(1) }}" rel="first" aria-label="Ke halaman pertama" title="Halaman pertama">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m11 7-5 5 5 5M18 7l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="pagination-label">Awal</span>
                </a>
                <a class="pagination-button pagination-edge-button" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Ke halaman sebelumnya" title="Halaman sebelumnya">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 6-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="pagination-label">Sebelumnya</span>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="pagination-button is-current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
                        @else
                            <a class="pagination-button" href="{{ $url }}" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-button pagination-edge-button" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Ke halaman berikutnya" title="Halaman berikutnya">
                    <span class="pagination-label">Berikutnya</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <a class="pagination-button pagination-edge-button" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label="Ke halaman terakhir" title="Halaman terakhir">
                    <span class="pagination-label">Akhir</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 7 5 5-5 5M13 7l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @else
                <span class="pagination-button pagination-edge-button is-disabled" aria-disabled="true" title="Halaman berikutnya">
                    <span class="pagination-label">Berikutnya</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="pagination-button pagination-edge-button is-disabled" aria-disabled="true" title="Halaman terakhir">
                    <span class="pagination-label">Akhir</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 7 5 5-5 5M13 7l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
