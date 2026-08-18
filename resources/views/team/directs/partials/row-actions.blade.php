@props(['member'])

@php
    $profileUrl = route('team.member.profile', $member);
    $summaryUrl = route('team.member', $member);
    $treeUrl = route('team.member.tree', $member);
@endphp

<div class="flex items-center justify-end gap-1.5">
    <a
        href="{{ $profileUrl }}"
        title="View profile details"
        aria-label="View profile details for {{ $member->name }}"
        class="group relative gw-icon-btn"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </svg>
        <span class="sr-only">View profile details</span>
    </a>
    <a
        href="{{ $summaryUrl }}"
        title="View member summary"
        aria-label="View member summary for {{ $member->name }}"
        class="group relative gw-icon-btn"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <span class="sr-only">View member summary</span>
    </a>
    <a
        href="{{ $treeUrl }}"
        title="View genealogy branch"
        aria-label="View genealogy branch for {{ $member->name }}"
        class="group relative gw-icon-btn"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 3v18"></path>
            <path d="M5 8h14"></path>
            <path d="M5 16h14"></path>
        </svg>
        <span class="sr-only">View genealogy branch</span>
    </a>
</div>
