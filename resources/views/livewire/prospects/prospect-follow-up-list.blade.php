<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
        <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                <h1 class="mt-2 text-2xl font-semibold">Follow-Up Center</h1>
                <p class="mt-2 text-sm text-slate-200">Track pending follow-ups, snooze reminders, and jump to prospect profiles.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('team.prospects') }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Dashboard</a>
                <a href="{{ route('team.prospects.pipeline') }}" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary">Sales Pipeline</a>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gw-accent/30 bg-white p-4 shadow-sm">
        <div class="grid gap-4 md:grid-cols-4">
            <label class="block text-sm font-semibold text-gw-primary">
                Status
                <select wire:model.live="statusFilter" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Pending &amp; Overdue</option>
                    <option value="pending">Pending</option>
                    <option value="overdue">Overdue</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-gw-primary">
                Priority
                <select wire:model.live="priorityFilter" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    <option value="">All priorities</option>
                    <option value="urgent">Urgent</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-gw-primary">
                Due from
                <input type="date" wire:model.live="dueFrom" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </label>
            <label class="block text-sm font-semibold text-gw-primary">
                Due to
                <input type="date" wire:model.live="dueTo" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            </label>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gw-accent/30 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-gw-primary text-left text-xs font-semibold uppercase tracking-wide text-gw-accent">
                    <tr>
                        <th class="px-4 py-3">Prospect</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Due</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Notes</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($followUps as $followUp)
                        <tr wire:key="follow-up-{{ $followUp->id }}" class="hover:bg-gw-accent-soft/60">
                            <td class="px-4 py-3">
                                <a href="{{ route('team.prospects.records.show', $followUp->prospect) }}" class="font-semibold text-gw-primary hover:text-gw-accent-dark">
                                    {{ $followUp->prospect->displayName() }}
                                </a>
                                <div class="mt-1 text-xs text-slate-500">{{ str($followUp->prospect->interest_level)->title() }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ str($followUp->followup_type)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-bold uppercase',
                                    'bg-red-100 text-red-700' => in_array($followUp->priority, ['urgent', 'high'], true),
                                    'bg-gw-accent-muted text-gw-accent-dark' => $followUp->priority === 'medium',
                                    'bg-slate-100 text-slate-600' => $followUp->priority === 'low',
                                ])>{{ str($followUp->priority)->title() }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $followUp->due_at->format('M j, Y g:i A') }}
                                @if ($followUp->due_at->isPast() && $followUp->status !== 'completed')
                                    <span class="ml-1 text-xs font-semibold text-red-600">Overdue</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ str($followUp->status)->title() }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Str::limit($followUp->notes, 60) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($followUp->status !== 'completed')
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="markComplete({{ $followUp->id }})" class="rounded border border-emerald-300 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">Complete</button>
                                        <button type="button" wire:click="snooze({{ $followUp->id }})" class="rounded border border-amber-300 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800 hover:bg-amber-100">Snooze +1d</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No follow-ups match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($followUps->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $followUps->links() }}
            </div>
        @endif
    </div>
</div>
