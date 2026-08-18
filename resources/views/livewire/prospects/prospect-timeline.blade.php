<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Unified history of CRM activity, meetings, tasks, FNA, files, and stage movement.</p>
        <select wire:model.live="filter" class="rounded-lg border-slate-300 text-sm">
            <option value="all">All events</option>
            <option value="activity">Activities</option>
            <option value="communication">Calls & communications</option>
            <option value="note">Notes</option>
            <option value="stage">Stage changes</option>
            <option value="calendar">Calendar events</option>
            <option value="appointment">Appointments</option>
            <option value="followup">Follow-ups</option>
            <option value="task">Tasks</option>
            <option value="fna">FNA</option>
            <option value="conversion">Conversions</option>
            <option value="file">Files</option>
        </select>
    </div>

    @php
        $typeStyles = [
            'activity' => 'bg-blue-100 text-blue-800',
            'communication' => 'bg-emerald-100 text-emerald-800',
            'note' => 'bg-amber-100 text-amber-800',
            'stage' => 'bg-violet-100 text-violet-800',
            'calendar' => 'bg-cyan-100 text-cyan-800',
            'appointment' => 'bg-sky-100 text-sky-800',
            'followup' => 'bg-lime-100 text-lime-800',
            'task' => 'bg-slate-100 text-slate-700',
            'fna' => 'bg-indigo-100 text-indigo-800',
            'conversion' => 'bg-rose-100 text-rose-800',
            'file' => 'bg-stone-100 text-stone-800',
        ];
        $typeLabels = [
            'activity' => 'Activity',
            'communication' => 'Communication',
            'note' => 'Note',
            'stage' => 'Stage',
            'calendar' => 'Calendar',
            'appointment' => 'Appointment',
            'followup' => 'Follow-Up',
            'task' => 'Task',
            'fna' => 'FNA',
            'conversion' => 'Conversion',
            'file' => 'File',
        ];
    @endphp

    @forelse ($timeline as $item)
        <article class="rounded-lg border border-slate-200 bg-white/90 px-4 py-3 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $typeStyles[$item['type']] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $typeLabels[$item['type']] ?? $item['type'] }}
                    </span>
                    <span class="text-sm font-semibold text-gw-primary">{{ $item['label'] }}</span>
                </div>
                <time class="text-xs text-slate-500">{{ $item['occurred_at'] }}</time>
            </div>
            @if ($item['body'])
                <p class="mt-2 text-sm text-slate-700">{{ $item['body'] }}</p>
            @endif
            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                <p>By {{ $item['actor'] }}</p>
                <div class="flex items-center gap-2">
                    @if (! empty($item['meta']))
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600">{{ $item['meta'] }}</span>
                    @endif
                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="font-semibold text-gw-accent-dark hover:text-gw-primary">Open</a>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-lg border border-dashed border-slate-300 bg-white/70 px-6 py-10 text-center">
            <p class="text-sm font-semibold text-gw-primary">No timeline events yet</p>
            <p class="mt-1 text-sm text-slate-500">
                @if ($filter === 'all')
                    Log an activity or add a note to build this prospect's history.
                @else
                    No {{ str($filter)->replace('_', ' ') }} events match this filter.
                @endif
            </p>
        </div>
    @endforelse
</div>
