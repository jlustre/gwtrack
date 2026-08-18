<div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-5">
    <div class="mb-4 flex flex-wrap gap-2 border-b border-zinc-800 pb-4">
        <button type="button" wire:click="$set('tab', 'tickets')" @class(['rounded-lg px-4 py-2 text-sm font-semibold transition duration-200', 'bg-amber-500 text-black' => $tab === 'tickets', 'text-zinc-400 hover:text-zinc-200' => $tab !== 'tickets'])>My tickets</button>
        <button type="button" wire:click="$set('tab', 'wishlist')" @class(['rounded-lg px-4 py-2 text-sm font-semibold transition duration-200', 'bg-amber-500 text-black' => $tab === 'wishlist', 'text-zinc-400 hover:text-zinc-200' => $tab !== 'wishlist'])>Community wishlist</button>
    </div>

    @if ($tab === 'tickets')
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Ticket</th>
                        <th class="px-3 py-2">Subject</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Urgency</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800 text-zinc-300">
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="px-3 py-2 font-mono text-amber-300">{{ $ticket->ticket_number }}</td>
                            <td class="px-3 py-2">{{ $ticket->subject }}</td>
                            <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" style="background-color: {{ $ticket->status?->color_hex }}22; color: {{ $ticket->status?->color_hex }}">{{ $ticket->status?->name }}</span></td>
                            <td class="px-3 py-2 capitalize">{{ $ticket->urgency }}</td>
                            <td class="px-3 py-2 text-right"><a href="{{ route('support.show', $ticket) }}" class="font-semibold text-amber-400 hover:text-amber-300">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-8 text-center text-zinc-500">No tickets yet. Use the wizard above to submit your first request.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    @else
        <div class="space-y-3">
            @forelse ($wishlistItems as $item)
                <article class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-zinc-100">{{ $item->title }}</h3>
                            <p class="mt-1 text-xs text-zinc-500">{{ config('support.modules.'.$item->module, $item->module) }} · {{ $item->votes_count }} votes</p>
                        </div>
                        <button type="button" wire:click="vote({{ $item->id }})" class="rounded-lg border border-amber-500/40 px-3 py-1.5 text-xs font-semibold text-amber-300 hover:bg-amber-500/10">Vote</button>
                    </div>
                </article>
            @empty
                <p class="py-8 text-center text-zinc-500">No enhancement ideas yet.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $wishlistItems->links() }}</div>
    @endif
</div>
