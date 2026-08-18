@props([
    'user' => null,
    'photoUrl' => null,
    'name' => null,
    'size' => 'md',
    'class' => '',
    'ring' => true,
])

@php
    $resolvedName = $name ?? $user?->name ?? 'Member';
    $resolvedPhotoUrl = $photoUrl ?? ($user ? $user->profilePhotoUrl() : null);
    $initials = $user?->initials() ?? \App\Support\UserAvatar::initials($resolvedName);

    $sizeClasses = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-11 w-11 text-sm',
        'lg' => 'h-16 w-16 text-lg',
        'xl' => 'h-20 w-20 text-2xl',
        default => 'h-11 w-11 text-sm',
    };

    $ringClasses = $ring ? 'border border-gw-accent/50' : '';
@endphp

<span {{ $attributes->merge(['class' => "relative inline-flex shrink-0 overflow-hidden rounded-full bg-gw-primary {$sizeClasses} {$ringClasses} {$class}"]) }}>
    @if ($resolvedPhotoUrl)
        <img
            src="{{ $resolvedPhotoUrl }}"
            alt="{{ $resolvedName }} profile photo"
            class="h-full w-full object-cover"
            loading="lazy"
            onerror="this.classList.add('hidden'); this.parentElement.querySelector('[data-avatar-fallback]')?.classList.remove('hidden');"
        >
    @endif
    <span
        data-avatar-fallback
        @class([
            'flex h-full w-full items-center justify-center font-bold text-gw-accent',
            'hidden' => (bool) $resolvedPhotoUrl,
        ])
    >{{ $initials }}</span>
</span>
