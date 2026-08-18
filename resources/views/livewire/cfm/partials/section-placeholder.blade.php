@php
    $current = collect($sections)->firstWhere('key', $activeSection);
    $phase = $current['phase'] ?? 2;
    $label = $current['label'] ?? ucfirst($activeSection);
@endphp

<div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gw-accent">Phase {{ $phase }} coming soon</p>
    <h3 class="mt-3 text-xl font-semibold text-gw-primary">{{ $label }}</h3>
    <p class="mx-auto mt-3 max-w-lg text-sm text-slate-600">
        This module is part of the multi-phase CFM Portal redesign. The overview dashboard and trainee 360° profile are live in Phase 1.
    </p>
    <button
        type="button"
        wire:click="setSection('overview')"
        class="mt-6 inline-flex items-center rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent transition hover:bg-[#102847]"
    >
        Back to overview
    </button>
</div>
