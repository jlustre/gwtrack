<div class="space-y-6">
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                ['label' => 'Open tickets', 'value' => $metrics['open_tickets'], 'theme' => 'navy', 'subtitle' => 'Active in queue'],
                ['label' => 'Urgent SLA breach', 'value' => $metrics['urgent_breaches'], 'theme' => 'red', 'subtitle' => 'Immediate response needed', 'pulse' => true],
                ['label' => 'At risk', 'value' => $metrics['at_risk'], 'theme' => 'amber', 'subtitle' => 'Approaching SLA deadline'],
                ['label' => 'Awaiting user', 'value' => $metrics['awaiting_user'], 'theme' => 'slate', 'subtitle' => 'Pending user reply'],
                ['label' => 'Wishlist submitted', 'value' => $metrics['wishlist_submitted'], 'theme' => 'gold', 'subtitle' => 'Enhancement requests'],
                ['label' => 'In development', 'value' => $metrics['wishlist_in_development'], 'theme' => 'emerald', 'subtitle' => 'Wishlist in progress'],
            ] as $card)
                <x-tracker-stat-card :label="$card['label']" :subtitle="$card['subtitle']" :theme="$card['theme']">
                    @if ($card['pulse'] ?? false)
                        <span class="animate-pulse">{{ $card['value'] }}</span>
                    @else
                        {{ $card['value'] }}
                    @endif
                </x-tracker-stat-card>
            @endforeach
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-5">
            <input
                type="search"
                wire:model.live.debounce.300ms="global_search"
                placeholder="Search tickets…"
                class="rounded-lg border-slate-300 bg-white p-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent lg:col-span-2"
            >
            <select wire:model.live="filter_status" class="rounded-lg border-slate-300 bg-white p-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filter_module" class="rounded-lg border-slate-300 bg-white p-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All modules</option>
                @foreach ($modules as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="filter_urgency" class="rounded-lg border-slate-300 bg-white p-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All urgency</option>
                @foreach ($urgencyLevels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model.live="toggle_sla_breach_only" class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
            SLA breach only
        </label>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Subject</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">SLA</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @foreach ($tickets as $ticket)
                    <tr wire:click="openTicket({{ $ticket->id }})" class="cursor-pointer transition hover:bg-gw-accent-soft/60">
                        <td class="px-4 py-3 font-mono font-semibold text-gw-accent-dark">{{ $ticket->ticket_number }}</td>
                        <td class="px-4 py-3 text-gw-primary">{{ $ticket->subject }}</td>
                        <td class="px-4 py-3">{{ $ticket->user?->name }}</td>
                        <td class="px-4 py-3">{{ $ticket->computed_priority_score ?? $ticket->priority_score }}</td>
                        <td class="px-4 py-3">
                            @if ($ticket->sla_status === 'overdue')
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 animate-pulse">Overdue</span>
                            @elseif ($ticket->sla_status === 'at_risk')
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">At risk</span>
                            @else
                                <span class="text-slate-500">On track</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $ticket->status?->name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-slate-200 p-4">{{ $tickets->links() }}</div>
    </div>

    @if ($selectedTicket)
        <div class="fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto bg-gw-primary/40 p-4 backdrop-blur-sm" wire:click.self="closeTicketModal">
            <div class="w-full max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-mono text-sm font-semibold text-gw-accent-dark">{{ $selectedTicket->ticket_number }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-gw-primary">{{ $selectedTicket->subject }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $selectedTicket->description }}</p>
                    </div>
                    <button type="button" wire:click="closeTicketModal" class="text-slate-400 transition hover:text-slate-600">✕</button>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Assign to</label>
                        <select wire:model="assign_to" class="mt-1 w-full rounded-lg border-slate-300 bg-white p-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            <option value="">Unassigned</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select wire:model="new_status_id" class="mt-1 w-full rounded-lg border-slate-300 bg-white p-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reply to user</label>
                    <textarea wire:model="agent_reply" rows="3" class="mt-1 w-full rounded-lg border-slate-300 bg-white p-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                </div>
                <div class="mt-3">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Internal note</label>
                    <textarea wire:model="internal_note" rows="2" class="mt-1 w-full rounded-lg border-slate-300 bg-white p-3 text-sm text-gw-primary shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" wire:click="saveTicketActions" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-primary-hover">Save changes</button>
                    <button type="button" wire:click="convertToWishlist" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-gw-accent hover:bg-gw-accent-soft">Convert to wishlist</button>
                </div>
            </div>
        </div>
    @endif
</div>
