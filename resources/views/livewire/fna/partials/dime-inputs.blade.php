@php
    $inputClass = 'mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent';
    $labelClass = 'block text-sm font-semibold text-slate-700';
@endphp

<div class="space-y-8">
    <div>
        <h4 class="text-sm font-bold uppercase tracking-wide text-gw-accent-dark">D — Debt</h4>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            @foreach (['credit_card_debt' => 'Credit Cards', 'personal_loans' => 'Personal Loans', 'car_loans' => 'Car Loans', 'student_loans' => 'Student Loans', 'business_debt' => 'Business Debt', 'final_expenses' => 'Final Expenses', 'other_debt' => 'Other Debt'] as $key => $label)
                <div>
                    <label class="{{ $labelClass }}">{{ $label }}</label>
                    <input type="number" step="0.01" min="0" wire:model.live.debounce.750ms="dime.{{ $key }}" class="{{ $inputClass }}">
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <h4 class="text-sm font-bold uppercase tracking-wide text-gw-accent-dark">I — Income Replacement</h4>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}">Annual Income to Replace</label>
                <input type="number" step="0.01" wire:model.live.debounce.750ms="dime.income_annual_to_replace" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Years to Replace</label>
                <input type="number" min="1" wire:model.live.debounce.750ms="dime.income_years_to_replace" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Existing Income Coverage</label>
                <input type="number" step="0.01" wire:model.live.debounce.750ms="dime.existing_income_replacement_coverage" class="{{ $inputClass }}">
            </div>
            <div class="flex items-center gap-2 pt-6">
                <input type="checkbox" wire:model.live="dime.income_inflation_adjustment" id="income_inflation" class="rounded border-slate-300 text-gw-accent">
                <label for="income_inflation" class="text-sm text-slate-700">Apply inflation adjustment</label>
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-bold uppercase tracking-wide text-gw-accent-dark">M — Mortgage</h4>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}">Mortgage Balance</label>
                <input type="number" step="0.01" wire:model.live.debounce.750ms="dime.mortgage_balance" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Monthly Payment</label>
                <input type="number" step="0.01" wire:model.live.debounce.750ms="dime.monthly_mortgage_payment" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Years Remaining</label>
                <input type="number" wire:model.live.debounce.750ms="dime.mortgage_years_remaining" class="{{ $inputClass }}">
            </div>
            <div class="flex items-center gap-2 pt-6">
                <input type="checkbox" wire:model.live="dime.include_mortgage_payoff" id="include_mortgage" class="rounded border-slate-300 text-gw-accent">
                <label for="include_mortgage" class="text-sm text-slate-700">Include mortgage payoff</label>
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-bold uppercase tracking-wide text-gw-accent-dark">E — Education</h4>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}">Number of Children</label>
                <input type="number" min="0" wire:model.live.debounce.750ms="dime.education_children_count" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Cost per Child</label>
                <input type="number" step="0.01" wire:model.live.debounce.750ms="dime.education_cost_per_child" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Years Until College</label>
                <input type="number" min="0" wire:model.live.debounce.750ms="dime.education_years_to_college" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Existing Education Savings</label>
                <input type="number" step="0.01" wire:model.live.debounce.750ms="dime.existing_education_savings" class="{{ $inputClass }}">
            </div>
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" wire:model.live="dime.education_inflation_adjustment" id="education_inflation" class="rounded border-slate-300 text-gw-accent">
                <label for="education_inflation" class="text-sm text-slate-700">Apply education inflation</label>
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-bold uppercase tracking-wide text-gw-accent-dark">Existing Resources</h4>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}">Existing Life Insurance</label>
                <input type="number" step="0.01" wire:model.live.debounce.750ms="dime.existing_life_insurance" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Liquid Assets Allocated</label>
                <input type="number" step="0.01" wire:model.live.debounce.750ms="dime.liquid_assets_allocated" class="{{ $inputClass }}">
            </div>
        </div>
    </div>

    <div>
        <label class="{{ $labelClass }}">Notes</label>
        <textarea wire:model.live.debounce.750ms="dime.notes" rows="3" class="{{ $inputClass }}"></textarea>
    </div>
</div>
