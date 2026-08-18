<?php

namespace App\Livewire\Concerns;

use App\Support\FnaWizardFieldOptions;
use App\Support\LocationOptions;
use Illuminate\Validation\ValidationException;

trait InteractsWithFnaClientInfoFields
{
    public function updatedCountry(): void
    {
        if (
            filled($this->state_province)
            && ! LocationOptions::isValidProvince($this->country, $this->state_province)
        ) {
            $this->state_province = null;
        }
    }

    protected function fnaClientInfoFieldOptions(bool $clientPortal = false): array
    {
        return FnaWizardFieldOptions::forWizard(
            $this->country,
            $clientPortal,
            $this->currentStep ?? 1,
        );
    }

    protected function validateFnaWizardStep(bool $clientPortal = false): void
    {
        $step = $this->currentStep ?? 1;
        $rules = FnaWizardFieldOptions::validationRulesForStep($step, $clientPortal);

        if ($rules === []) {
            return;
        }

        if (isset($rules['state_province'])) {
            $country = $this->country;
            $rules['state_province'][] = function (string $attribute, mixed $value, \Closure $fail) use ($country): void {
                if (! LocationOptions::isValidProvince($country, $value)) {
                    $fail('Please select a valid state or province for your country.');
                }
            };
        }

        $this->validate($rules);
    }

    protected function assertFnaClientPortalReadyToSubmit(): void
    {
        foreach (array_keys(config('fna.client_portal_step_required_fields', [])) as $step) {
            $originalStep = $this->currentStep;
            $this->currentStep = (int) $step;

            try {
                $this->validateFnaWizardStep(clientPortal: true);
            } catch (ValidationException $exception) {
                $this->currentStep = (int) $step;

                throw $exception;
            }

            $this->currentStep = $originalStep;
        }
    }
}
