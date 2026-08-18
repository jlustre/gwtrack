<div class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Sent (24h)', 'value' => number_format($metrics['sent_24h']), 'theme' => 'navy'],
            ['label' => 'Sent (7d)', 'value' => number_format($metrics['sent_7d']), 'theme' => 'cyan'],
            ['label' => 'Failed (24h)', 'value' => number_format($metrics['failed_24h']), 'theme' => 'red'],
            ['label' => 'Unread inbox', 'value' => number_format($metrics['unread_total']), 'theme' => 'gold'],
            ['label' => 'Critical open', 'value' => number_format($metrics['critical_open']), 'theme' => 'red'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Top triggers (7d)</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse ($metrics['top_triggers'] as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="font-mono text-xs text-gw-primary">{{ $row->trigger_code }}</span>
                        <span class="font-semibold">{{ number_format($row->total) }}</span>
                    </li>
                @empty
                    <li class="text-slate-500">No delivery activity yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Channel breakdown (7d)</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @forelse ($metrics['channel_breakdown'] as $row)
                    <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="capitalize">{{ str_replace('_', ' ', $row->channel) }}</span>
                        <span class="font-semibold">{{ number_format($row->total) }}</span>
                    </li>
                @empty
                    <li class="text-slate-500">No channel data yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <a href="{{ route('admin.management.index', ['category' => 'notifications']) }}" class="rounded-xl border border-gw-accent/40 bg-gw-accent-soft p-4 transition hover:bg-gw-accent/10">
            <p class="text-sm font-semibold text-gw-primary">Configuration tables</p>
            <p class="mt-1 text-xs text-slate-600">Types, triggers, templates, and escalation rules.</p>
        </a>
        <a href="{{ route('admin.notifications.delivery-logs') }}" class="rounded-xl border border-gw-primary/10 bg-white p-4 shadow-sm transition hover:border-gw-accent/40">
            <p class="text-sm font-semibold text-gw-primary">Delivery logs</p>
            <p class="mt-1 text-xs text-slate-600">Review sent, failed, and queued deliveries.</p>
        </a>
        <div class="rounded-xl border border-gw-primary/10 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-gw-primary">Operations snapshot</p>
            <p class="mt-2 text-xs text-slate-600">{{ number_format($metrics['active_escalations']) }} escalation events (7d)</p>
            <p class="text-xs text-slate-600">{{ number_format($metrics['inactive_triggers']) }} inactive triggers</p>
        </div>
    </div>

    @can('manage notification templates')
        <div class="rounded-2xl border border-gw-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gw-primary">Test send</h2>
            <p class="mt-1 text-sm text-slate-600">Queue a test notification to your account for any active trigger.</p>
            <div class="mt-4 flex flex-wrap items-end gap-3">
                <div class="min-w-[16rem] flex-1">
                    <label for="testTriggerId" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Trigger</label>
                    <select id="testTriggerId" wire:model="testTriggerId" class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        <option value="">Select trigger…</option>
                        @foreach ($triggers as $trigger)
                            <option value="{{ $trigger->id }}">{{ $trigger->name }} ({{ $trigger->code }})</option>
                        @endforeach
                    </select>
                    @error('testTriggerId')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="button" wire:click="sendTestNotification" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#132a4d]">
                    Send test
                </button>
            </div>
        </div>
    @endcan
</div>
