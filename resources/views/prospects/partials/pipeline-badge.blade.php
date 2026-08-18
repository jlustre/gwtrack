@php
    $pipelineKey = $prospect->funnel_type ?? 'insurance';
    $pipelineLabel = $prospect->pipelineLabel();
@endphp
<span @class([
    'inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
    'bg-gw-accent-muted text-gw-accent-dark' => $pipelineKey === 'insurance',
    'bg-emerald-100 text-emerald-800' => $pipelineKey === 'recruiting',
    'bg-violet-100 text-violet-800' => $pipelineKey === 'both',
])>{{ $pipelineLabel }}</span>
