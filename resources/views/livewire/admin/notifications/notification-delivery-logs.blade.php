<div class="space-y-4">
    <div class="rounded-2xl border border-gw-primary/10 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-4">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search trigger, user, failure…"
                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent md:col-span-2"
            >
            <select wire:model.live="status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All statuses</option>
                <option value="sent">Sent</option>
                <option value="failed">Failed</option>
                <option value="skipped">Skipped</option>
                <option value="suppressed">Suppressed</option>
                <option value="queued">Queued</option>
            </select>
            <select wire:model.live="channel" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All channels</option>
                <option value="in_app">In-app</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
                <option value="push">Push</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gw-primary/10 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Trigger</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Channel</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Failure</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->attempted_at?->format('M j, Y g:i A') }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $log->trigger_code ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?: '—' }}</td>
                        <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $log->channel) }}</td>
                        <td class="px-4 py-3">
                            @if ($log->status === 'failed')
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Failed</span>
                            @elseif ($log->status === 'sent')
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Sent</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ ucfirst($log->status) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 max-w-xs truncate text-xs text-slate-500">{{ $log->failure_reason ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('manage notification settings')
                                @if ($log->status === 'failed')
                                    <button type="button" wire:click="resend({{ $log->id }})" class="text-xs font-semibold text-gw-accent-dark hover:text-gw-primary">
                                        Resend
                                    </button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">No delivery logs match your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
</div>
