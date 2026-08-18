@php
    $progress = is_array($value) ? $value : ['started' => true, 'percent' => (int) $value];
@endphp
<div>
    <div class="flex items-center justify-between text-xs font-semibold text-slate-600">
        <span>{{ $label }}</span>
        <span>{{ \App\Support\ChecklistProgressDisplay::label($progress) }}</span>
    </div>
    <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full bg-gw-accent" style="width: {{ \App\Support\ChecklistProgressDisplay::percent($progress) }}%"></div>
    </div>
</div>
