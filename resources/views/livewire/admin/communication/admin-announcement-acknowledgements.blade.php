<div class="space-y-6">
    <div class="overflow-hidden rounded-2xl border border-gw-primary/10 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gw-primary">Announcement</th>
                    <th class="px-4 py-3 text-left font-semibold text-gw-primary">Priority</th>
                    <th class="px-4 py-3 text-left font-semibold text-gw-primary">Audience</th>
                    <th class="px-4 py-3 text-left font-semibold text-gw-primary">Acknowledged</th>
                    <th class="px-4 py-3 text-left font-semibold text-gw-primary">Pending</th>
                    <th class="px-4 py-3 text-left font-semibold text-gw-primary">Completion</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($report as $row)
                    <tr wire:key="ack-report-{{ $row['id'] }}">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gw-primary">{{ $row['title'] }}</div>
                            @if (! empty($row['category']))
                                <div class="text-xs text-slate-500">{{ $row['category'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $row['priority'] }}</td>
                        <td class="px-4 py-3">{{ number_format($row['audience_total']) }}</td>
                        <td class="px-4 py-3 text-emerald-700">{{ number_format($row['acknowledged_count']) }}</td>
                        <td class="px-4 py-3 text-red-600">{{ number_format($row['pending_count']) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-20 rounded-full bg-slate-200">
                                    <div class="h-2 rounded-full bg-gw-accent" style="width: {{ min(100, $row['completion_percent']) }}%"></div>
                                </div>
                                <span>{{ $row['completion_percent'] }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                wire:click="showDetail({{ $row['id'] }})"
                                class="text-xs font-semibold text-gw-accent-dark hover:text-gw-primary"
                            >
                                Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">No acknowledgement-required announcements yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($detail)
        <div class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-gw-primary">Pending users</h2>
                <button type="button" wire:click="closeDetail" class="text-sm font-semibold text-slate-500 hover:text-gw-primary">Close</button>
            </div>
            <dl class="mb-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <dt class="text-xs text-slate-500">Audience</dt>
                    <dd class="text-lg font-semibold text-gw-primary">{{ number_format($detail['audience_total']) }}</dd>
                </div>
                <div class="rounded-lg bg-emerald-50 px-3 py-2">
                    <dt class="text-xs text-emerald-700">Acknowledged</dt>
                    <dd class="text-lg font-semibold text-emerald-800">{{ number_format($detail['acknowledged_count']) }}</dd>
                </div>
                <div class="rounded-lg bg-red-50 px-3 py-2">
                    <dt class="text-xs text-red-700">Pending</dt>
                    <dd class="text-lg font-semibold text-red-800">{{ number_format($detail['pending_count']) }}</dd>
                </div>
            </dl>
            @if ($detail['pending_users'] !== [])
                <ul class="max-h-64 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-3 text-sm">
                    @foreach ($detail['pending_users'] as $pendingUser)
                        <li wire:key="pending-user-{{ $pendingUser['id'] }}" class="text-slate-700">{{ $pendingUser['name'] }}</li>
                    @endforeach
                </ul>
                @if ($detail['pending_count'] > count($detail['pending_users']))
                    <p class="mt-2 text-xs text-slate-500">Showing first {{ count($detail['pending_users']) }} of {{ number_format($detail['pending_count']) }} pending users.</p>
                @endif
            @else
                <p class="text-sm text-emerald-700">Everyone in the audience has acknowledged this announcement.</p>
            @endif
        </div>
    @endif
</div>
