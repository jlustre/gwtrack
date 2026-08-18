<div>
    <div
        @class([
            'fixed inset-0 z-50 flex items-center justify-center p-4',
            'hidden' => ! $show,
        ])
        role="dialog"
        aria-modal="true"
        aria-labelledby="prospect-quick-log-title"
        @if (! $show) aria-hidden="true" @endif
    >
        <div class="absolute inset-0 bg-gw-primary/60" wire:click="close"></div>
        <div class="relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg border border-gw-accent/40 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft shadow-xl">
            <div class="border-b border-slate-200 bg-gw-primary px-6 py-4 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Prospect Activity</p>
                        <h3 id="prospect-quick-log-title" class="mt-1 text-lg font-semibold">{{ $prospectName ?? 'Prospect' }}</h3>
                        <p class="mt-1 text-sm text-slate-300">Log calls, communications, notes, or update status and funnel stage.</p>
                    </div>
                    <button type="button" wire:click="close" class="text-2xl leading-none text-slate-300 hover:text-white">&times;</button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-slate-200 bg-white/90 px-4 py-3">
                @foreach (['activity' => 'Activity', 'communication' => 'Communication', 'note' => 'Note', 'status' => 'Status & Stage'] as $tab => $label)
                    <button
                        type="button"
                        wire:click="setTab('{{ $tab }}')"
                        @class([
                            'rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                            'bg-gw-primary text-gw-accent' => $activeTab === $tab,
                            'bg-white text-slate-600 hover:bg-gw-accent-soft' => $activeTab !== $tab,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="overflow-y-auto p-6">
                @if ($activeTab === 'activity' && $prospectId)
                    <livewire:prospects.prospect-activity-log-form
                        :prospect-id="$prospectId"
                        :initial-activity-type="$initialActivityType"
                        :embedded="true"
                        :key="'quick-log-activity-form-'.$prospectId.'-'.$activityFormKey"
                    />
                @endif

                @if ($activeTab === 'communication')
                    <form wire:submit="saveCommunication" class="grid gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Communication Type</span>
                            <select wire:model="communication_type_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                <option value="">Select type</option>
                                @foreach ($communicationTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Direction</span>
                            <select wire:model="direction" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                <option value="outbound">Outbound</option>
                                <option value="inbound">Inbound</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Contacted At</span>
                            <input wire:model="contacted_at" type="datetime-local" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            @error('contacted_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Duration (minutes)</span>
                            <input wire:model="duration_minutes" type="number" min="1" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm font-semibold text-slate-700">Outcome</span>
                            <input wire:model="communication_outcome" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm font-semibold text-slate-700">Notes</span>
                            <textarea wire:model="communication_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></textarea>
                        </label>
                        <div class="flex justify-end gap-2 md:col-span-2">
                            <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Save Communication</button>
                        </div>
                    </form>
                @endif

                @if ($activeTab === 'note')
                    <form wire:submit="saveNote" class="space-y-3">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Note</span>
                            <textarea wire:model="noteBody" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" placeholder="Internal note about this prospect…"></textarea>
                            @error('noteBody') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input wire:model="noteIsPrivate" type="checkbox" class="rounded border-slate-300">
                            Private note
                        </label>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Save Note</button>
                        </div>
                    </form>
                @endif

                @if ($activeTab === 'status')
                    <form wire:submit="saveStatus" class="grid gap-3 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Status</span>
                            <select wire:model="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                @foreach ($statusOptions as $option)
                                    <option value="{{ $option }}">{{ str($option)->title() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Funnel Stage</span>
                            <select wire:model="pipeline_stage_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                <option value="">Keep current stage</option>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage['id'] }}">{{ $stage['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Interest Level</span>
                            <select wire:model="interest_level" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                @foreach (['cold', 'warm', 'hot'] as $level)
                                    <option value="{{ $level }}">{{ str($level)->title() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Priority</span>
                            <select wire:model="priority" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                @foreach (['low', 'medium', 'high', 'urgent'] as $level)
                                    <option value="{{ $level }}">{{ str($level)->title() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="flex justify-end gap-2 md:col-span-2">
                            <button type="button" wire:click="close" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                            <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Update Prospect</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
