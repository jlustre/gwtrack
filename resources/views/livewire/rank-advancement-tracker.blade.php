<div class="space-y-6">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Career Progression</p>
                    <h1 class="mt-2 text-2xl font-semibold">Rank Advancement</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        @if ($tracker['is_self'])
                            Track your path from Field Associate through Executive Partner. Complete each requirement and submit evidence for leadership review.
                        @else
                            Reviewing rank advancement for <strong>{{ $tracker['member']['name'] }}</strong>.
                        @endif
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div class="rounded-md bg-white/10 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-300">Current rank</div>
                        <div class="mt-1 font-semibold">{{ $tracker['member']['rank'] }}</div>
                    </div>
                    <div class="rounded-md bg-white/10 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-300">Next rank</div>
                        <div class="mt-1 font-semibold">{{ $tracker['next_rank']['name'] ?? 'Max rank' }}</div>
                    </div>
                    <div class="rounded-md bg-white/10 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-300">Sponsor</div>
                        <div class="mt-1 font-semibold">{{ $tracker['member']['sponsor'] }}</div>
                    </div>
                    <div class="rounded-md bg-white/10 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-300">CFM</div>
                        <div class="mt-1 font-semibold">{{ $tracker['member']['mentor'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Next rank progress', 'value' => $tracker['percent'].'%', 'theme' => 'gold'],
                ['label' => 'Completed', 'value' => $tracker['stats']['completed'], 'theme' => 'emerald'],
                ['label' => 'In review', 'value' => $tracker['stats']['in_review'], 'theme' => 'amber'],
                ['label' => 'Remaining', 'value' => $tracker['stats']['remaining'], 'theme' => 'slate'],
            ] as $card)
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :theme="$card['theme']"
                />
            @endforeach
        </div>
    </div>

    @if (session('rank_advancement_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('rank_advancement_status') }}
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gw-primary">Rank ladder</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($tracker['rank_ladder'] as $step)
                @php
                    $classes = match ($step['state']) {
                        'achieved' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
                        'current' => 'border-gw-accent bg-gw-accent-soft text-gw-accent-dark',
                        'next' => 'border-gw-primary bg-gw-primary text-white',
                        default => 'border-slate-200 bg-slate-50 text-slate-500',
                    };
                @endphp
                <div class="rounded-full border px-3 py-1 text-xs font-semibold {{ $classes }}">
                    {{ $step['code'] }} · {{ $step['name'] }}
                </div>
            @endforeach
        </div>
    </div>

    @if ($tracker['at_max_rank'])
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-sm text-emerald-900">
            You have reached the highest active rank. Congratulations on your leadership journey.
        </div>
    @else
        @if ($tracker['can_review'] && count($tracker['review_queue']) > 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Review queue</h2>
                <p class="mt-1 text-sm text-slate-600">Requirements waiting for leadership confirmation.</p>
                <ul class="mt-4 space-y-3">
                    @foreach ($tracker['review_queue'] as $item)
                        <li class="rounded-lg border border-amber-100 bg-white p-4" wire:key="review-{{ $item['id'] }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gw-primary">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item['status_label'] }} · Submitted {{ $item['submitted_at'] ?? '—' }}</p>
                                    @if ($item['member_notes'])
                                        <p class="mt-2 text-sm text-slate-600">Member note: {{ $item['member_notes'] }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="openRequirement({{ $item['id'] }})" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Review</button>
                                </div>
                            </div>
                            @if ($activeProgressId === $item['id'])
                                <div class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                                    <textarea wire:model="reviewerNotes" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Reviewer notes (optional)"></textarea>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="approveRequirement({{ $item['id'] }})" class="rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">Approve</button>
                                        <button type="button" wire:click="rejectRequirement({{ $item['id'] }})" class="rounded-md border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Return for revision</button>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @foreach ($tracker['requirement_groups'] as $group)
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm" wire:key="group-{{ $group['category'] }}">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gw-primary">{{ $group['label'] }}</h2>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($group['items'] as $item)
                        <li class="px-6 py-4" wire:key="req-{{ $item['requirement_id'] }}">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-gw-primary">{{ $item['title'] }}</p>
                                        @if (! $item['is_required'])
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-500">Optional</span>
                                        @endif
                                    </div>
                                    @if ($item['description'])
                                        <p class="mt-1 text-sm text-slate-600">{{ $item['description'] }}</p>
                                    @endif
                                    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item['status_label'] }}</p>
                                    @if ($item['reviewer_notes'])
                                        <p class="mt-2 text-sm text-amber-800">Reviewer: {{ $item['reviewer_notes'] }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($tracker['is_self'] && in_array($item['status'], config('rank-advancement.member_actionable', []), true))
                                        @if ($item['status'] === 'not_started')
                                            <button type="button" wire:click="startRequirement({{ $item['id'] }})" class="rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-gw-primary-hover">Start</button>
                                        @endif
                                        @if (in_array($item['status'], ['in_progress', 'rejected'], true))
                                            <button type="button" wire:click="openRequirement({{ $item['id'] }})" class="rounded-md border border-gw-accent bg-gw-accent-soft px-3 py-1.5 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent-muted">Submit for review</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            @if ($activeProgressId === $item['id'] && $tracker['is_self'])
                                <div class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                                    <textarea wire:model="memberNotes" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Add notes for your reviewer (optional)"></textarea>
                                    <button type="button" wire:click="submitRequirement({{ $item['id'] }})" class="rounded-md bg-gw-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-gw-primary-hover">Submit requirement</button>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    @endif

    <p class="text-xs text-slate-500">
        Requirement definitions are seeded placeholders. Update qualifications in Admin → Rank Requirements when your official advancement criteria are ready.
    </p>
</div>
