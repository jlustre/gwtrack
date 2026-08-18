@php
    $inputClass = 'mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent';
    $labelClass = 'block text-sm font-semibold text-slate-700';
@endphp

<div>
    @if ($submitted)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-6 py-8 text-center">
            <h2 class="text-xl font-semibold text-emerald-900">Thank you!</h2>
            <p class="mt-3 text-sm text-emerald-800">Your financial needs analysis has been submitted to your advisor. They will review your information and follow up with you.</p>
        </div>
    @else
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

        <div>
            <h2 class="text-lg font-semibold text-gw-primary">{{ $steps[$currentStep] ?? 'Step' }}</h2>

            @include('livewire.fna.client.partials.wizard-step-fields')

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
                    <button type="button" wire:click="autosave" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Save Progress</button>
                    @if ($currentStep === 9)
                        <button type="button" wire:click="submitToAgent" wire:confirm="Submit your FNA to your advisor?" class="rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Submit to Advisor</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
