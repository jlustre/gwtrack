@php($center = $sectionCenter)
@php($promotion = $center['promotion'] ?? [])

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Rank advancement</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="refreshPromotionReadiness" class="inline-flex rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Refresh readiness</button>
                <a href="{{ $center['rank_advancement_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">Rank tracker</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Readiness', 'value' => ($center['stats']['readiness_percent'] ?? 0).'%', 'theme' => ($center['stats']['readiness_percent'] ?? 0) >= 90 ? 'emerald' : 'gold'],
            ['label' => 'Requirements met', 'value' => $center['stats']['requirements_met'] ?? 0, 'theme' => 'emerald'],
            ['label' => 'Remaining', 'value' => $center['stats']['requirements_remaining'] ?? 0, 'theme' => ($center['stats']['requirements_remaining'] ?? 0) > 0 ? 'amber' : 'emerald'],
            ['label' => 'Status', 'value' => ucfirst($center['stats']['status'] ?? 'tracking'), 'theme' => 'slate'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-slate-600">{{ $promotion['current_rank'] ?? '—' }} → <span class="font-semibold text-gw-primary">{{ $promotion['target_rank'] ?? '—' }}</span></p>
                <p class="mt-1 text-xs text-slate-500">Last updated {{ $promotion['updated_at'] ?? '—' }}</p>
            </div>
            <div class="w-full max-w-md">
                <div class="flex items-center justify-between text-xs font-semibold uppercase text-slate-500">
                    <span>Overall readiness</span>
                    <span>{{ $promotion['readiness_percent'] ?? 0 }}%</span>
                </div>
                <div class="mt-2 h-3 w-full rounded-full bg-slate-200">
                    <div class="h-3 rounded-full bg-gw-accent" style="width: {{ min(100, (int) ($promotion['readiness_percent'] ?? 0)) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-emerald-900">Requirements met</h3>
            @if (count($promotion['requirements_met'] ?? []) === 0)
                <p class="mt-4 text-sm text-emerald-800">No requirements fully met yet.</p>
            @else
                <ul class="mt-4 space-y-2">
                    @foreach ($promotion['requirements_met'] as $item)
                        <li class="rounded-lg bg-white/70 px-3 py-2 text-sm text-emerald-900">{{ $item['label'] }} ({{ $item['current'] }}%)</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-amber-900">Still needed</h3>
            @if (count($promotion['requirements_remaining'] ?? []) === 0)
                <p class="mt-4 text-sm text-amber-800">All tracked requirements are met.</p>
            @else
                <ul class="mt-4 space-y-2">
                    @foreach ($promotion['requirements_remaining'] as $item)
                        <li class="rounded-lg bg-white/70 px-3 py-2 text-sm text-amber-900">{{ $item['label'] }} — {{ $item['current'] }}% / {{ $item['target'] }}%</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @if (count($promotion['rank_requirements'] ?? []) > 0)
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Next rank checklist</h3>
            </div>
            <ul class="divide-y divide-slate-200">
                @foreach ($promotion['rank_requirements'] as $requirement)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <span class="text-gw-primary">{{ $requirement['title'] }}</span>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase',
                            'bg-emerald-100 text-emerald-800' => $requirement['status'] === 'completed',
                            'bg-slate-100 text-slate-700' => $requirement['status'] !== 'completed',
                        ])>{{ str_replace('_', ' ', $requirement['status']) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-gw-accent/30 bg-gw-accent-soft p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-gw-primary">Promotion status</h3>
        <form wire:submit="updatePromotionStatus" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="text-xs font-semibold text-slate-600">CFM tracking status</label>
                <select wire:model="promotionStatus" class="mt-1 w-full rounded-md border-slate-200 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    @foreach ($center['statuses'] as $status)
                        <option value="{{ $status }}" @selected($promotion['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-gw-accent hover:bg-[#102847]">Update status</button>
        </form>
    </div>
</div>
