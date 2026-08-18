@php
    $inputClass = 'mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent';
    $labelClass = 'block text-sm font-semibold text-slate-700';
@endphp

<div>
    {{-- Progress --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3" x-data="{ stepsOpen: false }">
        <div class="w-full sm:w-auto">
            <button type="button" x-on:click="stepsOpen = ! stepsOpen" class="mb-2 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 sm:hidden">
                Step {{ $currentStep }}: {{ $steps[$currentStep] ?? 'Wizard' }}
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': stepsOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="flex-wrap gap-1" :class="stepsOpen ? 'flex' : 'hidden sm:flex'">
                @foreach ($steps as $num => $label)
                    <button type="button" wire:click="goToStep({{ $num }})"
                        class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $currentStep === $num ? 'bg-gw-primary text-white' : ($currentStep > $num ? 'bg-gw-accent text-gw-primary' : 'bg-slate-100 text-slate-600') }}">
                        {{ $num }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="text-sm text-slate-600">
            <span class="font-semibold text-gw-primary">{{ $completenessScore }}%</span> complete
            @if ($saveStatus)
                <span class="ml-2 text-emerald-600">· {{ $saveStatus }}</span>
            @endif
        </div>
    </div>

    <div class="mb-4 flex gap-2 border-b border-slate-200 pb-2">
        <button type="button" wire:click="setTab('wizard')" class="px-3 py-1.5 text-sm font-semibold {{ $activeTab === 'wizard' ? 'border-b-2 border-gw-accent text-gw-primary' : 'text-slate-500' }}">Form Steps</button>
        <button type="button" wire:click="setTab('dime')" class="px-3 py-1.5 text-sm font-semibold {{ $activeTab === 'dime' ? 'border-b-2 border-gw-accent text-gw-primary' : 'text-slate-500' }}">DIME Analysis</button>
    </div>

    @if ($activeTab === 'dime')
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6">
                @include('livewire.fna.partials.dime-inputs')
                <div class="mt-6 flex gap-2">
                    <button type="button" wire:click="saveDime" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Save DIME to FNA</button>
                    <button type="button" wire:click="setTab('wizard')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Back to Form</button>
                </div>
            </div>
            <div class="lg:col-span-1">
                @include('livewire.fna.partials.dime-result-panel', [
                    'result' => $dimeResult,
                    'gapSummary' => $gapSummary ?? null,
                    'complianceNotice' => $complianceNotice ?? null,
                ])
            </div>
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-gw-primary">{{ $steps[$currentStep] ?? 'Step' }}</h2>

            @if ($currentStep === 1)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @include('livewire.fna.partials.wizard-client-info-fields', array_merge($clientInfoFieldOptions, [
                        'inputClass' => $inputClass,
                        'labelClass' => $labelClass,
                    ]))
                </div>
            @elseif ($currentStep === 2)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><label class="{{ $labelClass }}">Spouse / Partner Name</label><input wire:model.live.debounce.750ms="household.spouse_partner_name" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Spouse / Partner Age</label><input type="number" wire:model.live.debounce.750ms="household.spouse_partner_age" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Number of Children</label><input type="number" wire:model.live.debounce.750ms="household.children_count" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Household Income</label><input type="number" step="0.01" wire:model.live.debounce.750ms="household.household_income" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Household Expenses</label><input type="number" step="0.01" wire:model.live.debounce.750ms="household.household_expenses" class="{{ $inputClass }}"></div>
                    <div class="sm:col-span-2"><label class="{{ $labelClass }}">Dependents Notes</label><textarea wire:model.live.debounce.750ms="household.dependents_notes" rows="3" class="{{ $inputClass }}"></textarea></div>
                </div>
            @elseif ($currentStep === 3)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><label class="{{ $labelClass }}">Annual Income</label><input type="number" step="0.01" wire:model.live.debounce.750ms="income.annual_income" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Monthly Income</label><input type="number" step="0.01" wire:model.live.debounce.750ms="income.monthly_income" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Spouse Annual Income</label><input type="number" step="0.01" wire:model.live.debounce.750ms="income.spouse_annual_income" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Business Income</label><input type="number" step="0.01" wire:model.live.debounce.750ms="income.business_income" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Passive Income</label><input type="number" step="0.01" wire:model.live.debounce.750ms="income.passive_income" class="{{ $inputClass }}"></div>
                    <div class="sm:col-span-2"><label class="{{ $labelClass }}">Expected Income Changes</label><textarea wire:model.live.debounce.750ms="income.expected_income_changes" rows="3" class="{{ $inputClass }}"></textarea></div>
                </div>
            @elseif ($currentStep === 4)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach (['mortgage_balance' => 'Mortgage Balance', 'rent_amount' => 'Rent', 'credit_card_debt' => 'Credit Cards', 'car_loans' => 'Car Loans', 'student_loans' => 'Student Loans', 'personal_loans' => 'Personal Loans', 'business_debt' => 'Business Debt', 'other_liabilities' => 'Other Liabilities'] as $key => $label)
                        <div><label class="{{ $labelClass }}">{{ $label }}</label><input type="number" step="0.01" wire:model.live.debounce.750ms="debt.{{ $key }}" class="{{ $inputClass }}"></div>
                    @endforeach
                </div>
            @elseif ($currentStep === 5)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach (['emergency_fund' => 'Emergency Fund', 'checking_savings' => 'Checking / Savings', 'retirement_accounts' => 'Retirement', 'investment_accounts' => 'Investments', 'real_estate_assets' => 'Real Estate', 'business_assets' => 'Business Assets', 'college_savings' => 'College Savings', 'other_assets' => 'Other Assets'] as $key => $label)
                        <div><label class="{{ $labelClass }}">{{ $label }}</label><input type="number" step="0.01" wire:model.live.debounce.750ms="assets.{{ $key }}" class="{{ $inputClass }}"></div>
                    @endforeach
                </div>
            @elseif ($currentStep === 6)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach (['existing_life_insurance_amount' => 'Total Life Insurance', 'term_coverage' => 'Term', 'whole_life_coverage' => 'Whole Life', 'universal_life_coverage' => 'Universal Life', 'group_insurance_coverage' => 'Group', 'disability_coverage' => 'Disability', 'critical_illness_coverage' => 'Critical Illness', 'long_term_care_coverage' => 'Long-Term Care'] as $key => $label)
                        <div><label class="{{ $labelClass }}">{{ $label }}</label><input type="number" step="0.01" wire:model.live.debounce.750ms="coverage.{{ $key }}" class="{{ $inputClass }}"></div>
                    @endforeach
                    <div class="sm:col-span-2"><label class="{{ $labelClass }}">Beneficiary Information</label><textarea wire:model.live.debounce.750ms="coverage.beneficiary_information" rows="3" class="{{ $inputClass }}"></textarea></div>
                    <div class="flex items-center gap-2"><input type="checkbox" wire:model.live="coverage.policy_review_needed" class="rounded border-slate-300 text-gw-accent"><label class="text-sm text-slate-700">Policy review needed</label></div>
                </div>
            @elseif ($currentStep === 7)
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($goalOptions as $key => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <input type="checkbox" wire:model.live="selected_goals" value="{{ $key }}" class="rounded border-slate-300 text-gw-accent">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="mt-4"><label class="{{ $labelClass }}">Goal Notes</label><textarea wire:model.live.debounce.750ms="goal_notes" rows="3" class="{{ $inputClass }}"></textarea></div>
            @elseif ($currentStep === 8)
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="{{ $labelClass }}">Main Financial Concern</label><textarea wire:model.live.debounce.750ms="risk.main_financial_concern" rows="3" class="{{ $inputClass }}"></textarea></div>
                    <div class="sm:col-span-2"><label class="{{ $labelClass }}">Health Considerations</label><textarea wire:model.live.debounce.750ms="risk.health_considerations" rows="2" class="{{ $inputClass }}"></textarea></div>
                    <div><label class="{{ $labelClass }}">Job Stability</label><select wire:model.live="risk.job_stability" class="{{ $inputClass }}"><option value="">—</option>@foreach (['stable','moderate','at_risk'] as $v)<option value="{{ $v }}">{{ str($v)->title() }}</option>@endforeach</select></div>
                    <div><label class="{{ $labelClass }}">Family Dependency</label><select wire:model.live="risk.family_dependency_level" class="{{ $inputClass }}"><option value="">—</option>@foreach (['low','medium','high'] as $v)<option value="{{ $v }}">{{ str($v)->title() }}</option>@endforeach</select></div>
                    <div><label class="{{ $labelClass }}">Emergency Fund</label><select wire:model.live="risk.emergency_fund_adequacy" class="{{ $inputClass }}"><option value="">—</option>@foreach (['adequate','partial','inadequate'] as $v)<option value="{{ $v }}">{{ str($v)->title() }}</option>@endforeach</select></div>
                    <div><label class="{{ $labelClass }}">Risk Tolerance</label><select wire:model.live="risk.risk_tolerance" class="{{ $inputClass }}"><option value="">—</option>@foreach (['conservative','moderate','aggressive'] as $v)<option value="{{ $v }}">{{ str($v)->title() }}</option>@endforeach</select></div>
                    <div><label class="{{ $labelClass }}">Urgency</label><select wire:model.live="risk.urgency_level" class="{{ $inputClass }}"><option value="">—</option>@foreach (['low','medium','high','urgent'] as $v)<option value="{{ $v }}">{{ str($v)->title() }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="{{ $labelClass }}">Current Protection Gap Notes</label><textarea wire:model.live.debounce.750ms="risk.current_protection_gap" rows="2" class="{{ $inputClass }}"></textarea></div>
                </div>
            @elseif ($currentStep === 9)
                <div class="mt-4 space-y-4">
                    <div><label class="{{ $labelClass }}">Main Needs Identified</label><textarea wire:model.live.debounce.750ms="main_needs_identified" rows="3" class="{{ $inputClass }}"></textarea></div>
                    <div><label class="{{ $labelClass }}">Recommended Next Action</label><textarea wire:model.live.debounce.750ms="recommended_next_action" rows="2" class="{{ $inputClass }}"></textarea></div>
                    <div><label class="{{ $labelClass }}">Follow-Up Date</label><input type="date" wire:model.live.debounce.750ms="follow_up_date" class="{{ $inputClass }}"></div>
                    <div><label class="{{ $labelClass }}">Associate Recommendation</label><textarea wire:model.live.debounce.750ms="associate_recommendation" rows="3" class="{{ $inputClass }}"></textarea></div>
                    <div><label class="{{ $labelClass }}">Summary Notes</label><textarea wire:model.live.debounce.750ms="summary_notes" rows="3" class="{{ $inputClass }}"></textarea></div>

                    @if (count($missingSections))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            <strong>Missing sections:</strong> {{ implode(', ', $missingSections) }}
                        </div>
                    @endif

                    @error('completeness')
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
                    @enderror

                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <p><strong>Status:</strong> {{ $fna->statusLabel() }}</p>
                        <p class="mt-1"><strong>DIME:</strong> {{ $fna->dime_completed ? 'Completed' : 'Not completed' }} · <strong>Gap:</strong> {{ $fna->protection_gap ? '$'.number_format((float) $fna->protection_gap, 0) : '—' }}</p>
                    </div>
                </div>
            @endif

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <div class="flex gap-2">
                    @if ($currentStep > 1)
                        <button type="button" wire:click="previousStep" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Previous</button>
                    @endif
                    @if ($currentStep < 9)
                        <button type="button" wire:click="nextStep" class="rounded-lg border border-gw-primary bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Next</button>
                    @endif
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="autosave" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Save Draft</button>
                    @if ($currentStep === 9)
                        <button type="button" wire:click="markReadyForReview" class="rounded-lg border border-gw-primary bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Mark Ready for Review</button>
                        <button type="button" wire:click="$dispatch('open-fna-submit-modal', { fnaId: '{{ $fna->id }}' })" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Submit to CFM</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
