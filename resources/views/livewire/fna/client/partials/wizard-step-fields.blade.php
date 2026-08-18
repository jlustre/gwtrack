@if ($currentStep === 1)
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        @include('livewire.fna.partials.wizard-client-info-fields', array_merge($clientInfoFieldOptions, [
            'nameLabel' => 'Your Name',
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
        <div><label class="{{ $labelClass }}">Additional Notes</label><textarea wire:model.live.debounce.750ms="summary_notes" rows="3" class="{{ $inputClass }}"></textarea></div>

        @if (count($missingSections))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>Sections to review:</strong> {{ implode(', ', $missingSections) }}
            </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            When you submit, your advisor will review your information and contact you to discuss next steps.
        </div>
    </div>
@endif
