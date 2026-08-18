<div class="space-y-6">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#F8F3E7] shadow-sm">
        <div class="border-b border-slate-100 bg-gw-primary px-6 py-6 text-white">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Ongoing Compliance</p>
                    <h1 class="mt-2 text-2xl font-semibold">License & Compliance Lifecycle</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        @if ($hub['is_self'])
                            Track renewals after initial licensing — state licenses, E&O, AML, carrier appointments, and continuing education.
                        @else
                            Compliance records for <strong>{{ $hub['member']['name'] }}</strong>.
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $hub['licensing_tracker_url'] }}" class="rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">
                        Initial licensing tracker
                    </a>
                    @if ($hub['is_self'])
                        <a href="{{ $hub['profile_licenses_url'] }}" class="rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">
                            Profile licenses
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'Total records', 'value' => $hub['stats']['total'], 'theme' => 'navy'],
                ['label' => 'Active', 'value' => $hub['stats']['active'], 'theme' => 'emerald'],
                ['label' => 'Renewal due', 'value' => $hub['stats']['renewal_due'], 'theme' => 'amber'],
                ['label' => 'Expired', 'value' => $hub['stats']['expired'], 'theme' => 'red'],
                ['label' => 'Pending review', 'value' => $hub['stats']['pending_verification'], 'theme' => 'cyan'],
            ] as $card)
                <x-tracker-stat-card
                    :label="$card['label']"
                    :value="$card['value']"
                    :theme="$card['theme']"
                />
            @endforeach
        </div>
    </div>

    @if (session('compliance_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('compliance_status') }}
        </div>
    @endif

    @if ($hub['can_edit'])
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="openCreateForm" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">
                Add compliance record
            </button>
            @if ($hub['is_self'])
                <button type="button" wire:click="syncLicenses" class="rounded-lg border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-accent-dark hover:bg-gw-accent-muted">
                    Sync from profile licenses
                </button>
            @endif
        </div>
    @endif

    @if ($showForm)
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">{{ $editingId ? 'Edit record' : 'Add compliance record' }}</h2>
            <form wire:submit="saveRecord" class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">Type</label>
                    <select wire:model.live="complianceType" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($hub['types'] as $key => $type)
                            <option value="{{ $key }}">{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">Title</label>
                    <input type="text" wire:model="title" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="e.g. California Life License renewal">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                @if ($typeConfig['tracks_jurisdiction'] ?? false)
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">Jurisdiction key</label>
                        <input type="text" wire:model="jurisdictionKey" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="United States|California">
                    </div>
                @endif
                @if ($typeConfig['tracks_identifier'] ?? false)
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">License / policy #</label>
                        <input type="text" wire:model="identifier" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                    </div>
                @endif
                @if ($typeConfig['tracks_carrier'] ?? false)
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">Carrier / provider</label>
                        <input type="text" wire:model="carrierName" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                    </div>
                @endif
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">Effective date</label>
                    <input type="date" wire:model="effectiveDate" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase text-slate-500">Expiration date</label>
                    <input type="date" wire:model="expirationDate" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                </div>
                @if ($typeConfig['tracks_credits'] ?? false)
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">Credits required</label>
                        <input type="number" step="0.5" wire:model="creditsRequired" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase text-slate-500">Credits earned</label>
                        <input type="number" step="0.5" wire:model="creditsEarned" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                    </div>
                @endif
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold uppercase text-slate-500">Notes</label>
                    <textarea wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea>
                </div>
                <div class="flex gap-2 md:col-span-2">
                    <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Save</button>
                    <button type="button" wire:click="cancelForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    @forelse ($hub['groups'] as $group)
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm" wire:key="group-{{ $group['type'] }}">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-lg font-semibold text-gw-primary">{{ $group['label'] }}</h2>
                @if ($group['description'])
                    <p class="mt-1 text-sm text-slate-600">{{ $group['description'] }}</p>
                @endif
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($group['items'] as $item)
                    <li class="px-6 py-4" wire:key="record-{{ $item['id'] }}">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-gw-primary">{{ $item['title'] }}</p>
                                    @php
                                        $badgeClass = match ($item['status']) {
                                            'active' => 'bg-emerald-50 text-emerald-800',
                                            'pending_renewal' => 'bg-amber-50 text-amber-800',
                                            'expired' => 'bg-red-50 text-red-800',
                                            'pending_verification' => 'bg-sky-50 text-sky-800',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                    @endphp
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $badgeClass }}">{{ $item['status_label'] }}</span>
                                </div>
                                <dl class="mt-2 grid gap-1 text-sm text-slate-600 sm:grid-cols-2">
                                    @if ($item['identifier'])
                                        <div><span class="font-medium text-slate-500">ID:</span> {{ $item['identifier'] }}</div>
                                    @endif
                                    @if ($item['carrier_name'])
                                        <div><span class="font-medium text-slate-500">Carrier:</span> {{ $item['carrier_name'] }}</div>
                                    @endif
                                    @if ($item['effective_date'])
                                        <div><span class="font-medium text-slate-500">Effective:</span> {{ $item['effective_date'] }}</div>
                                    @endif
                                    @if ($item['expiration_date'])
                                        <div><span class="font-medium text-slate-500">Expires:</span> {{ $item['expiration_date'] }}
                                            @if ($item['days_until_expiration'] !== null)
                                                ({{ $item['days_until_expiration'] }} days)
                                            @endif
                                        </div>
                                    @endif
                                    @if ($item['credits_required'])
                                        <div><span class="font-medium text-slate-500">CE:</span> {{ $item['credits_earned'] ?? 0 }} / {{ $item['credits_required'] }}</div>
                                    @endif
                                </dl>
                                @if ($item['notes'])
                                    <p class="mt-2 text-sm text-slate-600">{{ $item['notes'] }}</p>
                                @endif
                            </div>
                            @if ($hub['can_edit'])
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="editRecord({{ $item['id'] }})" class="text-xs font-semibold text-gw-accent-dark hover:text-gw-primary">Edit</button>
                                    @if ($hub['can_manage'] && ! $item['is_verified'])
                                        <button type="button" wire:click="verifyRecord({{ $item['id'] }})" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">Verify</button>
                                    @endif
                                    @if ($item['compliance_type'] !== 'state_license')
                                        <button type="button" wire:click="deleteRecord({{ $item['id'] }})" wire:confirm="Remove this compliance record?" class="text-xs font-semibold text-red-600 hover:text-red-800">Remove</button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
            <p class="text-sm text-slate-600">No compliance records yet.</p>
            @if ($hub['is_self'])
                <p class="mt-2 text-sm text-slate-500">Sync licenses from your profile or add E&O, AML, carrier appointments, and CE tracking.</p>
            @endif
        </div>
    @endforelse
</div>
