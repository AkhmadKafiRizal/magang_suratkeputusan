@php
    $toastTypes = [
        'success' => ['title' => 'Berhasil', 'icon' => 'check'],
        'error' => ['title' => 'Terjadi Kesalahan', 'icon' => 'alert'],
        'warning' => ['title' => 'Perhatian', 'icon' => 'warning'],
        'info' => ['title' => 'Informasi', 'icon' => 'info'],
    ];

    $toasts = collect($toastTypes)
        ->map(fn (array $config, string $type) => [
            ...$config,
            'type' => $type,
            'message' => session($type),
        ])
        ->filter(fn (array $toast) => filled($toast['message']));

    $authFeedback = session('auth_feedback');
@endphp

@if (is_array($authFeedback) && filled($authFeedback['message'] ?? null))
    <div class="auth-feedback-layer" data-toast-container data-auth-feedback aria-label="Notifikasi autentikasi">
        <div
            class="auth-feedback-card"
            data-toast
            data-toast-timeout="3200"
            role="status"
            aria-live="polite"
            aria-atomic="true"
        >
            <button class="auth-feedback-close" type="button" data-toast-close aria-label="Tutup notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17" stroke-linecap="round"/></svg>
            </button>
            <span class="auth-feedback-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m5 12.5 4.2 4L19 6.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="auth-feedback-eyebrow">Sistem Arsip Surat</span>
            <strong>{{ $authFeedback['title'] ?? 'Berhasil' }}</strong>
            <p>{{ $authFeedback['message'] }}</p>
            <span class="auth-feedback-progress" aria-hidden="true"></span>
        </div>
    </div>
@endif

@if ($toasts->isNotEmpty())
    <div class="toast-region" data-toast-region aria-label="Notifikasi">
        @foreach ($toasts as $toast)
            <div
                class="app-toast app-toast-{{ $toast['type'] }}"
                data-toast
                data-toast-timeout="4000"
                role="{{ $toast['type'] === 'error' ? 'alert' : 'status' }}"
                aria-live="{{ $toast['type'] === 'error' ? 'assertive' : 'polite' }}"
                aria-atomic="true"
            >
                <span class="toast-icon" aria-hidden="true">
                    @if ($toast['icon'] === 'check')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @elseif ($toast['icon'] === 'alert')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01" stroke-linecap="round"/></svg>
                    @elseif ($toast['icon'] === 'warning')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 4.2 2.7 17.4A1.7 1.7 0 0 0 4.2 20h15.6a1.7 1.7 0 0 0 1.5-2.6L13.7 4.2a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01" stroke-linecap="round"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01" stroke-linecap="round"/></svg>
                    @endif
                </span>
                <span class="toast-copy">
                    <strong>{{ $toast['title'] }}</strong>
                    <span>{{ $toast['message'] }}</span>
                </span>
                <button class="toast-close" type="button" data-toast-close aria-label="Tutup notifikasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17" stroke-linecap="round"/></svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
