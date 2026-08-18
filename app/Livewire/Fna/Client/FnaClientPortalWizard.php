<?php

namespace App\Livewire\Fna\Client;

use App\Livewire\Concerns\InteractsWithFnaClientInfoFields;
use App\Models\FnaClientInvite;
use App\Models\FnaRecord;
use App\Services\Fna\FnaClientInviteService;
use App\Services\Fna\FnaClientPortalService;
use App\Services\Fna\FnaCompletenessService;
use App\Support\FnaClientPortalSession;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FnaClientPortalWizard extends Component
{
    use InteractsWithFnaClientInfoFields;

    public string $token;

    public ?FnaClientInvite $invite = null;

    public FnaRecord $fna;

    public int $currentStep = 1;

    public string $saveStatus = '';

    public int $completenessScore = 0;

    public bool $submitted = false;

    public string $client_name = '';

    public ?string $client_email = null;

    public ?string $client_phone = null;

    public ?string $date_of_birth = null;

    public ?string $gender = null;

    public ?string $marital_status = null;

    public ?string $occupation = null;

    public ?string $employer_business = null;

    public ?string $city = null;

    public ?string $state_province = null;

    public ?string $country = null;

    public ?string $preferred_contact_method = null;

    public ?string $best_contact_time = null;

    public array $household = [];

    public array $income = [];

    public array $debt = [];

    public array $assets = [];

    public array $coverage = [];

    public array $selected_goals = [];

    public ?string $goal_notes = null;

    public array $risk = [];

    public ?string $main_needs_identified = null;

    public ?string $recommended_next_action = null;

    public ?string $follow_up_date = null;

    public ?string $associate_recommendation = null;

    public ?string $summary_notes = null;

    protected bool $isHydrating = false;

    public function mount(string $token, FnaClientInviteService $invites, FnaClientPortalService $portal): void
    {
        $this->token = $token;
        $this->invite = $invites->findByToken($token);

        abort_unless($this->invite !== null, 404);
        abort_unless($this->invite->isUsable(), 410);

        FnaClientPortalSession::assertVerifiedFor($this->invite);

        $this->fna = $this->invite->fnaRecord;
        $this->submitted = $this->invite->status === 'submitted';

        $this->isHydrating = true;
        $state = $portal->getWizardState($this->fna);
        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        $this->isHydrating = false;
    }

    public function updated($property): void
    {
        if ($this->isHydrating || $this->submitted) {
            return;
        }

        if (in_array($property, ['currentStep', 'saveStatus', 'completenessScore'], true)) {
            return;
        }

        $this->autosave();
    }

    public function goToStep(int $step): void
    {
        if ($this->submitted) {
            return;
        }

        $this->currentStep = max(1, min(9, $step));
        $this->autosave();
    }

    public function nextStep(): void
    {
        if ($this->submitted || $this->currentStep >= 9) {
            return;
        }

        $this->validateFnaWizardStep(clientPortal: true);

        $this->currentStep++;
        $this->autosave();
    }

    public function previousStep(): void
    {
        if ($this->submitted || $this->currentStep <= 1) {
            return;
        }

        $this->currentStep--;
        $this->autosave();
    }

    public function autosave(): void
    {
        if ($this->submitted) {
            return;
        }

        $this->fna = app(FnaClientPortalService::class)->saveProgress($this->invite, $this->payload());
        $this->saveStatus = 'Saved '.now()->format('g:i A');
        $this->completenessScore = app(FnaCompletenessService::class)->score($this->fna);
        $this->invite = $this->invite->fresh();
    }

    public function submitToAgent(FnaClientInviteService $invites): void
    {
        if ($this->submitted) {
            return;
        }

        $this->assertFnaClientPortalReadyToSubmit();
        $this->autosave();
        $invites->markSubmitted($this->invite->fresh());
        $this->submitted = true;
        FnaClientPortalSession::clear();
        session()->flash('fna_client_status', 'Thank you! Your financial needs analysis has been submitted to your advisor.');
    }

    public function render(): View
    {
        return view('livewire.fna.client.fna-client-portal-wizard', [
            'steps' => config('fna.wizard_steps', []),
            'goalOptions' => config('fna.goal_options', []),
            'missingSections' => app(FnaCompletenessService::class)->missingSections($this->fna),
            'clientInfoFieldOptions' => $this->fnaClientInfoFieldOptions(clientPortal: true),
        ])->layout('fna.client.portal', [
            'title' => 'Financial Needs Analysis',
            'invite' => $this->invite,
        ]);
    }

    protected function payload(): array
    {
        return [
            'client_name' => $this->client_name,
            'client_email' => $this->client_email,
            'client_phone' => $this->client_phone,
            'date_of_birth' => $this->date_of_birth ?: null,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'occupation' => $this->occupation,
            'employer_business' => $this->employer_business,
            'city' => $this->city,
            'state_province' => $this->state_province,
            'country' => $this->country,
            'preferred_contact_method' => $this->preferred_contact_method,
            'best_contact_time' => $this->best_contact_time,
            'main_needs_identified' => $this->main_needs_identified,
            'recommended_next_action' => $this->recommended_next_action,
            'follow_up_date' => $this->follow_up_date ?: null,
            'associate_recommendation' => $this->associate_recommendation,
            'summary_notes' => $this->summary_notes,
            'current_step' => $this->currentStep,
            'household' => $this->normalizeNumericArray($this->household),
            'income' => $this->normalizeNumericArray($this->income),
            'debt' => $this->normalizeNumericArray($this->debt),
            'assets' => $this->normalizeNumericArray($this->assets),
            'coverage' => $this->normalizeCoverage($this->coverage),
            'goals' => [
                'selected_goals' => array_values($this->selected_goals),
                'goal_notes' => $this->goal_notes,
            ],
            'risk' => $this->risk,
        ];
    }

    protected function normalizeNumericArray(array $data): array
    {
        return collect($data)->map(function ($value, $key) {
            if (in_array($key, ['expected_income_changes', 'dependents_notes', 'beneficiary_information'], true)) {
                return $value;
            }

            return $value === '' || $value === null ? null : $value;
        })->all();
    }

    protected function normalizeCoverage(array $data): array
    {
        $data = $this->normalizeNumericArray($data);
        $data['policy_review_needed'] = (bool) ($data['policy_review_needed'] ?? false);

        return $data;
    }
}
