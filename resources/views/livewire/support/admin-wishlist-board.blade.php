<div class="overflow-x-auto pb-4">
    <div class="flex min-w-max gap-4">
        @foreach ($statuses as $statusKey => $statusLabel)
            <div class="w-72 shrink-0 rounded-2xl border border-zinc-800 bg-zinc-900">
                <div class="border-b border-zinc-800 px-4 py-3">
                    <h3 class="text-sm font-semibold text-zinc-100">{{ $statusLabel }}</h3>
                    <p class="text-xs text-zinc-500">{{ ($columns[$statusKey] ?? collect())->count() }} items</p>
                </div>
                <div class="space-y-3 p-3">
                    @foreach ($columns[$statusKey] ?? [] as $item)
                        <article
                            wire:key="wishlist-{{ $item->id }}"
                            class="cursor-pointer rounded-xl border border-zinc-800 bg-zinc-950 p-3 transition duration-200 ease-in-out hover:border-amber-500/40"
                            wire:click="openItem({{ $item->id }})"
                        >
                            <h4 class="text-sm font-semibold text-zinc-100">{{ $item->title }}</h4>
                            <p class="mt-1 text-xs text-zinc-500">{{ config('support.modules.'.$item->module, $item->module) }}</p>
                            <div class="mt-2 flex items-center justify-between text-xs">
                                <span class="text-amber-300">{{ $item->votes_count }} votes</span>
                                <span class="text-zinc-500">Score {{ $item->admin_priority_score }}</span>
                            </div>
                            @if ($statusKey !== 'released')
                                <div class="mt-3 flex flex-wrap gap-1">
                                    @php $keys = array_keys($statuses); $idx = array_search($statusKey, $keys, true); @endphp
                                    @if ($idx !== false && isset($keys[$idx + 1]))
                                        <button type="button" wire:click.stop="moveItem({{ $item->id }}, '{{ $keys[$idx + 1] }}')" class="rounded bg-amber-500/20 px-2 py-0.5 text-[10px] font-semibold text-amber-300">Move →</button>
                                    @endif
                                </div>
                            @else
                                <span class="mt-2 inline-flex rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-300">Released</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@if ($selectedItem)
    <div class="fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto bg-black/70 p-4" wire:click.self="closeItem">
        <div class="w-full max-w-2xl rounded-2xl border border-zinc-700 bg-zinc-900 p-6">
            <div class="flex items-start justify-between">
                <h3 class="text-lg font-semibold text-zinc-100">{{ $selectedItem->title }}</h3>
                <button type="button" wire:click="closeItem" class="text-zinc-400">✕</button>
            </div>
            <p class="mt-2 text-sm text-zinc-400">{{ $selectedItem->problem_solved }}</p>
            <p class="mt-3 text-sm text-zinc-300">{{ $selectedItem->suggested_description }}</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs uppercase text-zinc-500">Complexity</label>
                    <select wire:model="development_complexity" class="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-950 p-3 text-sm text-zinc-100">
                        <option value="">Select</option>
                        @foreach ($complexities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs uppercase text-zinc-500">Effort (hours)</label>
                    <input type="number" wire:model="estimated_effort_hours" min="1" class="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-950 p-3 text-sm text-zinc-100">
                </div>
                <div>
                    <label class="text-xs uppercase text-zinc-500">Target release</label>
                    <input type="date" wire:model="target_release_date" class="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-950 p-3 text-sm text-zinc-100">
                </div>
                <div>
                    <label class="text-xs uppercase text-zinc-500">Pipeline status</label>
                    <select wire:model="status" class="mt-1 w-full rounded-lg border border-zinc-700 bg-zinc-950 p-3 text-sm text-zinc-100">
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="button" wire:click="saveItem" class="mt-4 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-black hover:bg-amber-600">Save item</button>
        </div>
    </div>
@endif
