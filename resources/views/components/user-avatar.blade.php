@props([
    'user' => null,
    'size' => 'small',
    'lazy' => false,
])

@php
    $name = trim((string) ($user?->name ?? ''));
    $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = collect(array_slice($words, 0, 2))
        ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
    $initials = $initials !== '' ? $initials : '?';
    $hasPhoto = $user !== null && filled($user->profile_photo_path);
    $avatarLabel = $user !== null
        ? 'Foto profil '.$user->name
        : 'Pelaku aktivitas tidak tersedia';
@endphp

<span
    {{ $attributes->class([
        'user-avatar',
        'user-avatar-'.$size,
        'user-avatar-system' => $user === null,
    ]) }}
    role="img"
    aria-label="{{ $avatarLabel }}"
>
    @if ($hasPhoto)
        <img
            class="user-avatar-image"
            src="{{ route('users.photo', $user) }}"
            alt=""
            decoding="async"
            @if ($lazy) loading="lazy" @endif
            data-user-avatar-image
        >
        <span class="user-avatar-fallback" data-user-avatar-fallback hidden>{{ $initials }}</span>
    @elseif ($user !== null)
        <span class="user-avatar-fallback">{{ $initials }}</span>
    @else
        <svg class="user-avatar-system-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M9.6 9a2.5 2.5 0 0 1 4.8.9c0 1.8-2.4 2.1-2.4 3.6M12 17h.01" stroke-linecap="round"/>
        </svg>
    @endif
</span>
