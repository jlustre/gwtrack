@php
    $items = $assignedByMe['items'] ?? [];
    $stats = $assignedByMe['stats'] ?? [];
@endphp

<section
    id="{{ $panelId }}"
    @class([
        'overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm',
        'mt-5' => ! $compact,
    ])
>
    <div @class([
        'border-b border-slate-200 bg-gradient-to-r from-gw-primary to-[#1E3A5F] text-white',
        'px-4 py-3' => $compact,
        'px-5 py-4' => ! $compact,
    ])>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Delegation Tracker</p>
                <h2 @class(['font-semibold text-white', 'text-sm' => $compact, 'text-lg' => ! $compact])>
                    Tasks I assigned to others
                </h2>
                @unless ($compact)
                    <p class="mt-1 text-sm text-slate-300">Track completion and progress for tasks you delegated to teammates.</p>
                @endunless
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @unless ($showArchived)
                    <div class="flex flex-wrap gap-2 text-[11px] font-semibold">
                        <span class="rounded-full bg-white/15 px-2.5 py-1 text-white">{{ ($stats['active'] ?? 0) }} active</span>
                        <span class="rounded-full bg-emerald-400/20 px-2.5 py-1 text-emerald-100">{{ ($stats['completed'] ?? 0) }} completed</span>
                        <span class="rounded-full bg-red-400/20 px-2.5 py-1 text-red-100">{{ ($stats['overdue'] ?? 0) }} overdue</span>
                    </div>
                @endunless
                <button
                    type="button"
                    wire:click="toggleArchived"
                    class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-white/20"
                >
                    {{ $showArchived ? 'Back to active' : 'Show archived' }}
                </button>
            </div>
        </div>
    </div>

    @if (session('delegation_tracker_status'))
        <div class="border-b border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-800">
            {{ session('delegation_tracker_status') }}
        </div>
    @endif

    @if ($items === [])
        <div @class(['text-center', 'px-4 py-8' => $compact, 'px-6 py-12' => ! $compact])>
            @if ($showArchived)
                <p class="text-sm font-semibold text-gw-primary">No archived delegations</p>
                <p class="mt-1 text-xs text-slate-500">Archived tasks stay hidden from your tracker until you restore them here.</p>
            @else
                <p class="text-sm font-semibold text-gw-primary">No delegated tasks yet</p>
                <p class="mt-1 text-xs text-slate-500">When you assign a task to someone else, it will appear here with live status updates.</p>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5">Task</th>
                        <th class="px-4 py-2.5">Assignee</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="hidden px-4 py-2.5 sm:table-cell">Priority</th>
                        <th class="hidden px-4 py-2.5 md:table-cell">Due</th>
                        @unless ($compact)
                            <th class="hidden px-4 py-2.5 lg:table-cell">Assigned</th>
                        @endunless
                        <th class="px-4 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($items as $row)
                        <tr wire:key="assigned-task-row-{{ $row['id'] ?? md5($row['title'] ?? '') }}" @class(['bg-slate-50/60' => $row['is_closed'] ?? false])>
                            <td class="px-4 py-3 align-top">
                                <p class="font-semibold text-gw-primary">{{ $row['title'] ?? 'Task' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $row['category'] ?? 'Task' }}</p>
                                @if (filled($row['related_person'] ?? null))
                                    <p class="mt-0.5 text-[11px] text-slate-400">Related: {{ $row['related_person'] }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gw-primary/10 text-[11px] font-bold text-gw-primary">
                                        {{ $row['assignee_initials'] ?? '?' }}
                                    </span>
                                    <span class="text-sm text-slate-700">{{ $row['assignee'] ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span @class([
                                    'inline-flex rounded-full border px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide',
                                    $row['status_badge_class'] ?? 'bg-slate-100 text-slate-700 border-slate-200',
                                ])>
                                    {{ $row['status'] ?? '—' }}
                                </span>
                                @if (($row['progress'] ?? 0) > 0 && ! ($row['is_closed'] ?? false))
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $row['progress'] }}% complete</p>
                                @endif
                                @if (filled($row['completed_at'] ?? null))
                                    <p class="mt-1 text-[11px] text-emerald-700">Done {{ $row['completed_at'] }}</p>
                                @endif
                            </td>
                            <td class="hidden px-4 py-3 align-top text-slate-600 sm:table-cell">{{ $row['priority'] ?? '—' }}</td>
                            <td @class(['hidden px-4 py-3 align-top md:table-cell', 'font-semibold text-red-700' => ($row['status_key'] ?? '') === 'overdue', 'text-slate-600' => ($row['status_key'] ?? '') !== 'overdue'])>
                                {{ $row['due_label'] ?? '—' }}
                            </td>
                            @unless ($compact)
                                <td class="hidden px-4 py-3 align-top text-slate-600 lg:table-cell">{{ $row['assigned_at'] ?? '—' }}</td>
                            @endunless
                            <td class="px-4 py-3 align-top text-right">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    @if ($showArchived)
                                        <button
                                            type="button"
                                            wire:click="restoreTask({{ $row['id'] }})"
                                            class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100"
                                        >
                                            Restore
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="archiveTask({{ $row['id'] }})"
                                            wire:confirm="Archive this delegation? It will be hidden from your tracker but the assignee keeps the task."
                                            class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100"
                                        >
                                            Archive
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="deleteTask({{ $row['id'] }})"
                                            wire:confirm="{{ ($row['is_closed'] ?? false) ? 'Remove this delegation from your tracker?' : 'Delete this delegation? The task will be cancelled for the assignee.' }}"
                                            class="rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 hover:bg-red-100"
                                        >
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
