@php
    use App\Support\ProspectDependents;

    $useLivewire = $useLivewire ?? true;
    $includeStatus = $includeStatus ?? false;
    $compact = $compact ?? false;
    $fc = 'mt-0.5 block w-full rounded-md border-slate-300 bg-white px-2.5 py-1.5 text-sm shadow-sm';
    $lc = 'text-xs font-semibold text-slate-600';

    if (! $useLivewire) {
        $selected = $selected ?? fn (string $field, mixed $default = null) => old($field, $prospect->{$field} ?? $default);
        $selectedStageId = old('pipeline_stage_id', $prospect->pipeline_stage_id);
        $selectedSourceId = old('prospect_source_id', $prospect->prospect_source_id);
        $selectedFnaStatus = old('fna_status', $prospect->fna_status ?? 'not_started');
        $nextFollowUp = old('next_follow_up_at', $prospect->next_follow_up_at?->format('Y-m-d\TH:i'));
        $selectedTraits = old('qualification_traits', $prospect->qualification_traits ?? []);
        $dependentRows = ProspectDependents::formRows(old('dependents', $prospect->dependents));
        $alpineDependentRows = array_values(array_map(
            fn (array $row): array => [
                'name' => (string) ($row['name'] ?? ''),
                'age' => filled($row['age'] ?? null) ? (string) $row['age'] : '',
            ],
            $dependentRows,
        ));
    } else {
        $dependentRows = $dependents ?? [];
        $selectedTraits = [];
    }

    $genders = config('prospects.genders', []);
    $maritalStatuses = config('prospects.marital_statuses', []);
    $traitOptions = config('prospects.qualification_traits', []);
    $fg = $compact
        ? 'grid grid-cols-1 gap-1.5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6'
        : 'grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
    $sectionsWrapper = $compact ? 'grid grid-cols-1 gap-2 xl:grid-cols-2' : 'space-y-2.5';
    $full = 'col-span-full';
    $wide = $compact ? 'md:col-span-2' : '';
    $addressWide = $compact ? 'col-span-full xl:col-span-3 2xl:col-span-4' : $full;
    $notesWide = $compact ? 'col-span-full lg:col-span-2 xl:col-span-3 2xl:col-span-4' : $full;
    $traitsGrid = $compact
        ? 'grid grid-cols-2 gap-1.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8'
        : 'grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
@endphp

@if (! $useLivewire)
    <input type="hidden" name="prospect_funnel_id" value="{{ old('prospect_funnel_id', $prospectFunnelId ?? '') }}">
@endif

<div class="{{ $sectionsWrapper }}">
        <x-prospects.form-section title="Pipeline & Source" description="Funnel, stage, and lead origin." :compact="$compact">
            <label class="block">
                <span class="{{ $lc }}">Funnel Type</span>
                @if ($useLivewire)
                    <select wire:model.live="funnel_type" class="{{ $fc }}">
                        @foreach ($funnelTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('funnel_type') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @else
                    <select name="funnel_type" class="{{ $fc }}">
                        @foreach ($funnelTypes as $value => $label)
                            <option value="{{ $value }}" @selected($selected('funnel_type', 'insurance') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('funnel_type') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Pipeline Stage</span>
                @if ($useLivewire)
                    <select wire:model="pipeline_stage_id" class="{{ $fc }}">
                        <option value="">Select stage</option>
                        @foreach ($stages as $stage)
                            <option value="{{ $stage['id'] }}">{{ $stage['label'] }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="pipeline_stage_id" class="{{ $fc }}">
                        <option value="">Select stage</option>
                        @foreach ($stages as $stage)
                            <option value="{{ $stage['id'] }}" @selected((string) $selectedStageId === (string) $stage['id'])>{{ $stage['label'] }}</option>
                        @endforeach
                    </select>
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Lead Source</span>
                @if ($useLivewire)
                    <select wire:model="prospect_source_id" class="{{ $fc }}">
                        <option value="">No source</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="prospect_source_id" class="{{ $fc }}">
                        <option value="">No source</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected((string) $selectedSourceId === (string) $source->id)>{{ $source->name }}</option>
                        @endforeach
                    </select>
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Referral Source</span>
                @if ($useLivewire)
                    <input wire:model="referral_source_name" class="{{ $fc }}">
                @else
                    <input name="referral_source_name" value="{{ $selected('referral_source_name') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Campaign</span>
                @if ($useLivewire)
                    <input wire:model="campaign_name" class="{{ $fc }}">
                @else
                    <input name="campaign_name" value="{{ $selected('campaign_name') }}" class="{{ $fc }}">
                @endif
            </label>
        </x-prospects.form-section>

        <x-prospects.form-section title="Contact & Qualification" description="Reachability and personal qualifying details." :compact="$compact" class="xl:col-span-2">
            <label class="block">
                <span class="{{ $lc }}">First Name</span>
                @if ($useLivewire)
                    <input wire:model="first_name" class="{{ $fc }}">
                    @error('first_name') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @else
                    <input name="first_name" value="{{ $selected('first_name') }}" class="{{ $fc }}">
                    @error('first_name') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Last Name</span>
                @if ($useLivewire)
                    <input wire:model="last_name" class="{{ $fc }}">
                @else
                    <input name="last_name" value="{{ $selected('last_name') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Preferred Name</span>
                @if ($useLivewire)
                    <input wire:model="preferred_name" class="{{ $fc }}">
                @else
                    <input name="preferred_name" value="{{ $selected('preferred_name') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block {{ $wide }}">
                <span class="{{ $lc }}">Email</span>
                @if ($useLivewire)
                    <input wire:model="email" type="email" class="{{ $fc }}">
                @else
                    <input name="email" type="email" value="{{ $selected('email') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Mobile</span>
                @if ($useLivewire)
                    <input wire:model="phone" class="{{ $fc }}">
                @else
                    <input name="phone" value="{{ $selected('phone') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Home Phone</span>
                @if ($useLivewire)
                    <input wire:model="home_phone" class="{{ $fc }}">
                @else
                    <input name="home_phone" value="{{ $selected('home_phone') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Work Phone</span>
                @if ($useLivewire)
                    <input wire:model="work_phone" class="{{ $fc }}">
                @else
                    <input name="work_phone" value="{{ $selected('work_phone') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Date of Birth</span>
                @if ($useLivewire)
                    <input wire:model="date_of_birth" type="date" class="{{ $fc }}">
                    @error('date_of_birth') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @else
                    <input name="date_of_birth" type="date" value="{{ $selected('date_of_birth', $prospect->date_of_birth?->format('Y-m-d')) }}" class="{{ $fc }}">
                    @error('date_of_birth') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Gender</span>
                @if ($useLivewire)
                    <select wire:model="gender" class="{{ $fc }}">
                        <option value="">Select</option>
                        @foreach ($genders as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="gender" class="{{ $fc }}">
                        <option value="">Select</option>
                        @foreach ($genders as $value => $label)
                            <option value="{{ $value }}" @selected($selected('gender') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Marital Status</span>
                @if ($useLivewire)
                    <select wire:model="marital_status" class="{{ $fc }}">
                        <option value="">Select</option>
                        @foreach ($maritalStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="marital_status" class="{{ $fc }}">
                        <option value="">Select</option>
                        @foreach ($maritalStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($selected('marital_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Profession / Occupation</span>
                @if ($useLivewire)
                    <input wire:model="occupation" class="{{ $fc }}" placeholder="e.g. Teacher, Nurse">
                    @error('occupation') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @else
                    <input name="occupation" value="{{ $selected('occupation') }}" class="{{ $fc }}" placeholder="e.g. Teacher, Nurse">
                    @error('occupation') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @endif
            </label>

            <label class="block {{ $wide }}">
                <span class="{{ $lc }}">Employer / Business</span>
                @if ($useLivewire)
                    <input wire:model="employer_business" class="{{ $fc }}">
                    @error('employer_business') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @else
                    <input name="employer_business" value="{{ $selected('employer_business') }}" class="{{ $fc }}">
                    @error('employer_business') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @endif
            </label>
        </x-prospects.form-section>

        <x-prospects.form-section title="Address" :compact="$compact">
            <label class="block {{ $addressWide }}">
                <span class="{{ $lc }}">Street Address</span>
                @if ($useLivewire)
                    <input wire:model="address_line_1" class="{{ $fc }}">
                @else
                    <input name="address_line_1" value="{{ $selected('address_line_1') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">City</span>
                @if ($useLivewire)
                    <input wire:model="city" class="{{ $fc }}">
                @else
                    <input name="city" value="{{ $selected('city') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">State / Province</span>
                @if ($useLivewire)
                    <input wire:model="state_province" class="{{ $fc }}">
                @else
                    <input name="state_province" value="{{ $selected('state_province') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Country</span>
                @if ($useLivewire)
                    <input wire:model="country" class="{{ $fc }}">
                @else
                    <input name="country" value="{{ $selected('country') }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Postal Code</span>
                @if ($useLivewire)
                    <input wire:model="postal_code" class="{{ $fc }}">
                @else
                    <input name="postal_code" value="{{ $selected('postal_code') }}" class="{{ $fc }}">
                @endif
            </label>
        </x-prospects.form-section>

        <x-prospects.form-section title="Scoring & Follow-Up" description="Priority and pipeline momentum." :compact="$compact">
            @if ($includeStatus || ! $useLivewire)
                <label class="block">
                    <span class="{{ $lc }}">Status</span>
                    @if ($useLivewire)
                        <select wire:model="status" class="{{ $fc }}">
                            @foreach (['active', 'inactive', 'archived'] as $statusOption)
                                <option value="{{ $statusOption }}">{{ str($statusOption)->title() }}</option>
                            @endforeach
                        </select>
                    @else
                        <select name="status" class="{{ $fc }}">
                            @foreach (['active', 'inactive', 'archived'] as $statusOption)
                                <option value="{{ $statusOption }}" @selected($selected('status', 'active') === $statusOption)>{{ str($statusOption)->title() }}</option>
                            @endforeach
                        </select>
                    @endif
                </label>
            @endif

            <label class="block">
                <span class="{{ $lc }}">Interest Level</span>
                @if ($useLivewire)
                    <select wire:model="interest_level" class="{{ $fc }}">
                        @foreach (['cold', 'warm', 'hot'] as $level)
                            <option value="{{ $level }}">{{ str($level)->title() }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="interest_level" class="{{ $fc }}">
                        @foreach (['cold', 'warm', 'hot'] as $level)
                            <option value="{{ $level }}" @selected($selected('interest_level', 'warm') === $level)>{{ str($level)->title() }}</option>
                        @endforeach
                    </select>
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Interest Score</span>
                @if ($useLivewire)
                    <input wire:model="interest_score" type="number" min="1" max="10" class="{{ $fc }}" placeholder="1-10">
                @else
                    <input name="interest_score" type="number" min="1" max="10" value="{{ $selected('interest_score') }}" class="{{ $fc }}" placeholder="1-10">
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Priority</span>
                @if ($useLivewire)
                    <select wire:model="priority" class="{{ $fc }}">
                        @foreach (['low', 'medium', 'high', 'urgent'] as $priorityOption)
                            <option value="{{ $priorityOption }}">{{ str($priorityOption)->title() }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="priority" class="{{ $fc }}">
                        @foreach (['low', 'medium', 'high', 'urgent'] as $priorityOption)
                            <option value="{{ $priorityOption }}" @selected($selected('priority', 'medium') === $priorityOption)>{{ str($priorityOption)->title() }}</option>
                        @endforeach
                    </select>
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">FNA Status</span>
                @if ($useLivewire)
                    <select wire:model="fna_status" class="{{ $fc }}">
                        @foreach ($fnaStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="fna_status" class="{{ $fc }}">
                        @foreach ($fnaStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedFnaStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
            </label>

            <label class="block">
                <span class="{{ $lc }}">Next Follow-Up</span>
                @if ($useLivewire)
                    <input wire:model="next_follow_up_at" type="datetime-local" class="{{ $fc }}">
                @else
                    <input name="next_follow_up_at" type="datetime-local" value="{{ $nextFollowUp }}" class="{{ $fc }}">
                @endif
            </label>

            <label class="block {{ $notesWide }}">
                <span class="{{ $lc }}">Follow-Up Notes</span>
                @if ($useLivewire)
                    <textarea wire:model="follow_up_notes" rows="{{ $compact ? 2 : 3 }}" class="{{ $fc }}" placeholder="Agenda, talking points, or context for the next touchpoint."></textarea>
                    @error('follow_up_notes') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @else
                    <textarea name="follow_up_notes" rows="3" class="{{ $fc }}" placeholder="Agenda, talking points, or context for the next touchpoint.">{{ $selected('follow_up_notes') }}</textarea>
                    @error('follow_up_notes') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @endif
            </label>
        </x-prospects.form-section>

        <x-prospects.form-section title="Spouse & Dependents" description="Household qualifying details." :compact="$compact" innerClass="space-y-2">
            <div class="{{ $fg }}">
                <label class="block">
                    <span class="{{ $lc }}">Spouse Name</span>
                    @if ($useLivewire)
                        <input wire:model="spouse_name" class="{{ $fc }}">
                        @error('spouse_name') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    @else
                        <input name="spouse_name" value="{{ $selected('spouse_name') }}" class="{{ $fc }}">
                        @error('spouse_name') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    @endif
                </label>

                <label class="block">
                    <span class="{{ $lc }}">Spouse Profession</span>
                    @if ($useLivewire)
                        <input wire:model="spouse_occupation" class="{{ $fc }}">
                        @error('spouse_occupation') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    @else
                        <input name="spouse_occupation" value="{{ $selected('spouse_occupation') }}" class="{{ $fc }}">
                        @error('spouse_occupation') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    @endif
                </label>

                <label class="block">
                    <span class="{{ $lc }}">Spouse Birthday</span>
                    @if ($useLivewire)
                        <input wire:model="spouse_date_of_birth" type="date" class="{{ $fc }}">
                        @error('spouse_date_of_birth') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    @else
                        <input name="spouse_date_of_birth" type="date" value="{{ $selected('spouse_date_of_birth', $prospect->spouse_date_of_birth?->format('Y-m-d')) }}" class="{{ $fc }}">
                        @error('spouse_date_of_birth') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    @endif
                </label>
            </div>

            <div class="border-t border-slate-100 pt-2">
                <p class="{{ $lc }} mb-2">Dependent Children</p>

                @if ($useLivewire)
                    <div class="space-y-2">
                        @foreach ($dependentRows as $index => $dependent)
                            <div class="flex flex-wrap items-end gap-2 rounded border border-slate-100 bg-slate-50/80 p-2">
                                <label class="block min-w-0 flex-1">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Name</span>
                                    <input wire:model="dependents.{{ $index }}.name" class="{{ $fc }}">
                                </label>
                                <label class="block w-20 shrink-0">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Age</span>
                                    <input wire:model="dependents.{{ $index }}.age" type="number" min="0" max="99" class="{{ $fc }}">
                                </label>
                                @if (count($dependentRows) > 1)
                                    <button type="button" wire:click="removeDependent({{ $index }})" class="shrink-0 rounded border border-red-200 bg-white px-2 py-1.5 text-[10px] font-semibold text-red-700 hover:bg-red-50">
                                        ×
                                    </button>
                                @endif
                            </div>
                        @endforeach

                        <button type="button" wire:click="addDependent" class="rounded border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                            Add more fields
                        </button>
                    </div>
                @else
                    <div
                        class="space-y-2"
                        x-data="{
                            rows: @js($alpineDependentRows),
                            addRow() {
                                if (this.rows.length < 12) {
                                    this.rows.push({ name: '', age: '' });
                                }
                            },
                            removeRow(index) {
                                if (this.rows.length <= 1) {
                                    return;
                                }

                                this.rows.splice(index, 1);
                            },
                        }"
                    >
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="flex flex-wrap items-end gap-2 rounded border border-slate-100 bg-slate-50/80 p-2">
                                <label class="block min-w-0 flex-1">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Name</span>
                                    <input type="text" class="{{ $fc }}" x-model="row.name" :name="`dependents[${index}][name]`">
                                </label>
                                <label class="block w-20 shrink-0">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Age</span>
                                    <input type="number" min="0" max="99" class="{{ $fc }}" x-model="row.age" :name="`dependents[${index}][age]`">
                                </label>
                                <button
                                    type="button"
                                    class="shrink-0 rounded border border-red-200 bg-white px-2 py-1.5 text-[10px] font-semibold text-red-700 hover:bg-red-50"
                                    x-show="rows.length > 1"
                                    x-on:click="removeRow(index)"
                                >
                                    ×
                                </button>
                            </div>
                        </template>

                        <button type="button" class="rounded border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50" x-on:click="addRow()">
                            Add more fields
                        </button>
                    </div>
                @endif

                @error('dependents') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>
        </x-prospects.form-section>

        <x-prospects.form-section title="Qualification Traits" description="Check all that apply." :compact="$compact" class="xl:col-span-2" :innerClass="$traitsGrid">
            @foreach ($traitOptions as $key => $label)
                <label class="flex items-center gap-2 rounded border border-slate-100 bg-slate-50/50 px-2 py-1.5 text-xs text-gw-primary">
                    @if ($useLivewire)
                        <input type="checkbox" wire:model="qualification_traits" value="{{ $key }}" class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                    @else
                        <input type="checkbox" name="qualification_traits[]" value="{{ $key }}" @checked(in_array($key, is_array($selectedTraits) ? $selectedTraits : [], true)) class="rounded border-slate-300 text-gw-accent focus:ring-gw-accent">
                    @endif
                    <span class="leading-tight">{{ $label }}</span>
                </label>
            @endforeach

            @if ($useLivewire)
                @error('qualification_traits') <p class="{{ $full }} mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
            @else
                @error('qualification_traits') <p class="{{ $full }} mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
            @endif
        </x-prospects.form-section>

        <x-prospects.form-section title="Qualification Notes" description="Income, goals, objections, timeline." :compact="$compact" class="xl:col-span-2">
            <label class="block {{ $notesWide }}">
                @if ($useLivewire)
                    <textarea wire:model="qualification_notes" rows="{{ $compact ? 2 : 3 }}" class="{{ $fc }}"></textarea>
                    @error('qualification_notes') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @else
                    <textarea name="qualification_notes" rows="3" class="{{ $fc }}">{{ $selected('qualification_notes') }}</textarea>
                    @error('qualification_notes') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @endif
            </label>
        </x-prospects.form-section>
</div>
