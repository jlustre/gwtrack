@php
    $rankCode = is_array($member)
        ? ($member['rank'] ?? 'FA')
        : ($member->rank?->code ?? 'FA');
@endphp

<span class="inline-flex items-center rounded-full border border-gw-accent bg-gw-accent-muted px-2.5 py-1 text-xs font-bold text-gw-primary">
    {{ $rankCode }}
</span>
