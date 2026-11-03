<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <header class="rounded-lg bg-gw-primary px-6 py-7 text-white">
        <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Platform administration</p>
        <h1 class="mt-2 text-2xl font-semibold">Rank Administration</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
            Manage the promotion ladder, compensation rates, and the advancement requirements members see in their tracker.
            Retiring ranks and requirements preserves existing member assignments and progress history.
        </p>
    </header>

    @if (session('rank-admin-status'))
        <div role="status" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('rank-admin-status') }}
        </div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Active ranks</p>
            <p class="mt-1 text-2xl font-semibold text-gw-primary">{{ $activeRankCount }}</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Members with rank progress history</p>
            <p class="mt-1 text-2xl font-semibold text-gw-primary">{{ $memberCount }}</p>
        </article>
    </section>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(24rem,0.9fr)]">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 p-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gw-primary">Promotion ladder</h2>
                    <p class="mt-1 text-sm text-slate-600">Ranks are ordered by sort value. Rank codes stay fixed after creation to protect existing references.</p>
                </div>
                <button type="button" wire:click="createRank" class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Add rank
                </button>
            </div>

            <div class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-[1fr_12rem]">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</span>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rank code or name" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Track</span>
                    <select wire:model.live="trackFilter" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        <option value="">All tracks</option>
                        <option value="producer">Producer</option>
                        <option value="builder">Builder</option>
                    </select>
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Rank</th>
                            <th class="px-4 py-3">Track / order</th>
                            <th class="px-4 py-3">FYO</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($ranks as $rank)
                            <tr wire:key="rank-{{ $rank->id }}" @class(['bg-blue-50/60' => $selectedRank?->id === $rank->id])>
                                <td class="px-4 py-3">
                                    <button type="button" wire:click="selectRank({{ $rank->id }})" class="text-left">
                                        <span class="block font-semibold text-gw-primary">{{ $rank->name }}</span>
                                        <span class="text-xs text-slate-500">{{ $rank->code }} · {{ $rank->users_count }} members · {{ $rank->requirements_count }} requirements</span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <span class="block capitalize">{{ $rank->track }}</span>
                                    <span class="text-xs">Order {{ $rank->sort_order }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $rank->total_fyo_rate !== null ? number_format((float) $rank->total_fyo_rate, 2).'%' : '—' }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-emerald-50 text-emerald-700' => $rank->is_active,
                                        'bg-slate-100 text-slate-600' => ! $rank->is_active,
                                    ])>{{ $rank->is_active ? 'Active' : 'Retired' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <button type="button" wire:click="moveRank({{ $rank->id }}, 'up')" aria-label="Move {{ $rank->name }} up" class="font-semibold text-slate-600 hover:text-gw-primary">↑</button>
                                    <button type="button" wire:click="moveRank({{ $rank->id }}, 'down')" aria-label="Move {{ $rank->name }} down" class="ml-1 font-semibold text-slate-600 hover:text-gw-primary">↓</button>
                                    <button type="button" wire:click="editRank({{ $rank->id }})" class="font-semibold text-blue-700 hover:text-blue-900">Edit</button>
                                    <button
                                        type="button"
                                        wire:click="toggleRankActive({{ $rank->id }})"
                                        wire:confirm="{{ $rank->is_active ? 'Retire this rank? Existing member rank assignments will be retained.' : 'Reactivate this rank?' }}"
                                        class="ml-3 font-semibold {{ $rank->is_active ? 'text-amber-700 hover:text-amber-900' : 'text-emerald-700 hover:text-emerald-900' }}"
                                    >{{ $rank->is_active ? 'Retire' : 'Activate' }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No ranks match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($ranks->hasPages())
                <div class="border-t border-slate-200 p-4">{{ $ranks->links() }}</div>
            @endif
        </section>

        <div class="space-y-6">
            @if ($showRankForm)
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gw-primary">{{ $editingRankId ? 'Edit rank' : 'Create rank' }}</h2>
                            <p class="mt-1 text-sm text-slate-600">Rates are percentage points (for example, enter 120 for 120%).</p>
                        </div>
                        <button type="button" wire:click="cancelRankForm" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel</button>
                    </div>

                    <form wire:submit="saveRank" class="mt-4 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Code</span>
                                <input wire:model="rankForm.code" maxlength="20" @readonly($editingRankId) class="mt-1 block w-full rounded-md border-slate-300 text-sm uppercase shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('rankForm.code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Name</span>
                                <input wire:model="rankForm.name" maxlength="255" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('rankForm.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Track</span>
                                <select wire:model="rankForm.track" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    <option value="producer">Producer</option>
                                    <option value="builder">Builder</option>
                                </select>
                                @error('rankForm.track') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sort order</span>
                                <input type="number" min="0" max="65535" wire:model="rankForm.sort_order" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('rankForm.sort_order') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Income metric</span>
                                <select wire:model="rankForm.income_metric" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    <option value="">None</option>
                                    <option value="lti">Lifetime income (LTI)</option>
                                    <option value="twelve_month_income">12-month income (12MI)</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Income requirement</span>
                                <input type="number" min="0" wire:model="rankForm.income_requirement" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('rankForm.income_requirement') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                            @foreach ([
                                'qualification' => 'Qualification note',
                                'nba_requirement' => 'NBA requirement',
                                'mb_leg_requirement' => 'Managing Broker legs',
                                'svp_leg_requirement' => 'SVP legs',
                                'svp_leg_alternate' => 'SVP alternate legs',
                                'compensation_rate' => 'Compensation rate %',
                                'ppb_rate' => 'PPB rate %',
                                'total_fyo_rate' => 'Total FYO rate %',
                                'non_fyc_rate' => 'Other provider non-FYC %',
                            ] as $field => $label)
                                <label class="block">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</span>
                                    <input @if (in_array($field, ['nba_requirement', 'mb_leg_requirement', 'svp_leg_requirement', 'svp_leg_alternate'], true)) type="number" min="0" step="1" @else type="number" min="0" max="500" step="0.01" @endif wire:model="rankForm.{{ $field }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    @error("rankForm.{$field}") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </label>
                            @endforeach
                        </div>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</span>
                            <textarea wire:model="rankForm.description" rows="3" maxlength="2000" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                            @error('rankForm.description') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>
                        <div class="flex flex-wrap gap-6">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" wire:model="rankForm.is_field_leader" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                                Field leader rank
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" wire:model="rankForm.is_active" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                                Active in promotion ladder
                            </label>
                        </div>
                        @error('rankForm.is_active') <span class="block text-xs text-red-700">{{ $message }}</span> @enderror
                        <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">Save rank</button>
                    </form>
                </section>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                @if ($selectedRank)
                    <div class="border-b border-slate-200 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Advancement requirements</p>
                        <h2 class="mt-1 text-lg font-semibold text-gw-primary">{{ $selectedRank->name }} <span class="text-sm font-normal text-slate-500">({{ $selectedRank->code }})</span></h2>
                        <p class="mt-1 text-sm text-slate-600">These appear when a member is advancing toward this rank.</p>
                    </div>
                    <div class="space-y-3 p-5">
                        @forelse ($requirements as $requirement)
                            <article wire:key="rank-requirement-{{ $requirement->id }}" class="rounded-md border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-slate-800">{{ $requirement->title }}</h3>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $categories[$requirement->category] ?? ucfirst($requirement->category) }}
                                            · Order {{ $requirement->sort_order }}
                                            · {{ $requirement->is_required ? 'Required' : 'Optional' }}
                                            · {{ $requirement->progress_records_count }} progress records
                                        </p>
                                        @if ($requirement->description)
                                            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $requirement->description }}</p>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 gap-3 text-sm">
                                        <button type="button" wire:click="editRequirement({{ $requirement->id }})" class="font-semibold text-blue-700">Edit</button>
                                        <button
                                            type="button"
                                            wire:click="toggleRequirementActive({{ $requirement->id }})"
                                            wire:confirm="{{ $requirement->is_active ? 'Retire this requirement? Existing progress records will be retained.' : 'Reactivate this requirement?' }}"
                                            class="font-semibold {{ $requirement->is_active ? 'text-amber-700' : 'text-emerald-700' }}"
                                        >{{ $requirement->is_active ? 'Retire' : 'Activate' }}</button>
                                    </div>
                                </div>
                                @unless ($requirement->is_active)
                                    <p class="mt-2 text-xs font-semibold text-amber-700">Retired — hidden from active advancement trackers.</p>
                                @endunless
                            </article>
                        @empty
                            <p class="rounded-md border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">No advancement requirements are configured for this rank.</p>
                        @endforelse
                    </div>

                    <form wire:submit="saveRequirement" class="border-t border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gw-primary">{{ $editingRequirementId ? 'Edit requirement' : 'Add advancement requirement' }}</h3>
                                <p class="mt-1 text-xs text-slate-600">Retiring a requirement keeps existing member progress history.</p>
                            </div>
                            @if ($editingRequirementId)
                                <button type="button" wire:click="cancelRequirementForm" class="text-sm font-semibold text-slate-600">Cancel edit</button>
                            @endif
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block sm:col-span-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Title</span>
                                <input wire:model="requirementForm.title" maxlength="255" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('requirementForm.title') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</span>
                                <select wire:model="requirementForm.category" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                    @foreach ($categories as $category => $label)
                                        <option value="{{ $category }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('requirementForm.category') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sort order</span>
                                <input type="number" min="0" max="65535" wire:model="requirementForm.sort_order" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('requirementForm.sort_order') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</span>
                                <textarea wire:model="requirementForm.description" rows="3" maxlength="2000" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                                @error('requirementForm.description') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-6">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" wire:model="requirementForm.is_required" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                                Required to advance
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" wire:model="requirementForm.is_active" class="rounded border-slate-300 text-gw-primary focus:ring-gw-accent">
                                Active in member tracker
                            </label>
                        </div>
                        <button type="submit" wire:loading.attr="disabled" class="mt-4 rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">Save requirement</button>
                    </form>
                @else
                    <div class="p-8 text-center">
                        <h2 class="font-semibold text-gw-primary">Choose a rank</h2>
                        <p class="mt-2 text-sm text-slate-600">Select a rank in the ladder to view and manage its advancement requirements.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
