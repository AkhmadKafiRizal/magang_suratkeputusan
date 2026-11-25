@php
    $showPerPage = $showPerPage ?? false;
    $perPageOptions = ['10' => '10', '25' => '25', '50' => '50'];
    $selectedPerPage = (string) request()->query('per_page', '10');
    $selectedPerPage = array_key_exists($selectedPerPage, $perPageOptions) ? $selectedPerPage : '10';
    $lastPage = $paginator->lastPage();
    $windowStart = max(1, min($paginator->currentPage() - 1, max(1, $lastPage - 2)));
    $windowEnd = min($lastPage, $windowStart + 2);
@endphp

@if ($paginator->hasPages() || $showPerPage)
    <nav class="app-pagination" role="navigation" aria-label="Navigasi halaman">
        <div class="pagination-summary-group">
            <p class="pagination-summary">
                Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}</strong>–<strong>{{ $paginator->lastItem() ?? 0 }}</strong>
                dari <strong>{{ $paginator->total() }}</strong> data
            </p>

            @if ($showPerPage)
                <form class="pagination-size-form" method="GET" action="{{ url()->current() }}">
                    @foreach (request()->only('search', 'status', 'kategori') as $name => $value)
                        @if (is_scalar($value))
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="pagination-per-page-{{ $paginator->getPageName() }}">Tampilkan</label>
                    <select
                        id="pagination-per-page-{{ $paginator->getPageName() }}"
                        name="per_page"
                        aria-label="Jumlah data per halaman"
                        onchange="this.form.submit()"
                    >
                        @foreach ($perPageOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedPerPage === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span>data</span>
                    <noscript><button class="pagination-size-submit" type="submit">Terapkan</button></noscript>
                </form>
            @endif
        </div>

        @if ($paginator->hasPages())
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

                @for ($page = $windowStart; $page <= $windowEnd; $page++)
                    @if ($page === $paginator->currentPage())
                        <span class="pagination-button is-current" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
                    @else
                        <a class="pagination-button" href="{{ $paginator->url($page) }}" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                    @endif
                @endfor

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
        @endif
    </nav>
@endif
