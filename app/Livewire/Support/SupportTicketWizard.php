<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Services\Support\SupportAttachmentService;
use App\Services\Support\SupportTicketService;
use App\Services\Support\SupportWishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class SupportTicketWizard extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public string $track = 'standard';

    public string $type = '';

    public string $module = '';

    public string $category = '';

    public string $user_intent_action = '';

    public string $user_reported_outcome = '';

    public string $subject = '';

    public string $description = '';

    public string $urgency = 'medium';

    public string $impact = 'self';

    public string $frequency = 'unknown';

    public string $device = 'unknown';

    public string $browser = 'unknown';

    public ?string $related_url = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachments = [];

    public string $feature_title = '';

    public string $related_module = '';

    public string $problem_solved = '';

    public string $suggested_description = '';

    public ?string $example_link = null;

    public string $user_priority = 'medium';

    /** @var list<string> */
    public array $business_value = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('submit support ticket'), 403);
    }

    public function selectTrack(string $track): void
    {
        $this->track = in_array($track, ['standard', 'enhancement', 'documentation'], true)
            ? $track
            : 'standard';
        $this->step = 1;

        if ($this->track === 'enhancement') {
            $this->type = 'enhancement';
        }
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));
        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(
        SupportTicketService $tickets,
        SupportWishlistService $wishlist,
        SupportAttachmentService $attachments,
    ): void {
        if ($this->track === 'enhancement') {
            $this->validate($this->enhancementRules());

            $item = $wishlist->create(auth()->user(), [
                'title' => $this->feature_title,
                'module' => $this->related_module,
                'problem_solved' => $this->problem_solved,
                'suggested_description' => $this->suggested_description,
                'example_link' => $this->example_link,
                'business_value' => $this->business_value,
                'user_priority' => $this->user_priority,
            ]);

            $attachments->attachMany($item, $this->attachments);

            session()->flash('support_status', 'Your enhancement idea was submitted. Thank you!');
            $this->redirect(route('support.index'), navigate: true);

            return;
        }

        $this->validate(array_merge(
            $this->rulesForStep(1),
            $this->rulesForStep(2),
            $this->rulesForStep(3),
            $this->rulesForStep(4),
        ));

        $ticket = $tickets->createTicket(auth()->user(), [
            'type' => $this->type,
            'module' => $this->module,
            'category' => $this->category,
            'user_intent_action' => $this->user_intent_action,
            'user_reported_outcome' => $this->user_reported_outcome,
            'subject' => $this->subject,
            'description' => $this->description,
            'urgency' => $this->urgency,
            'impact' => $this->impact,
            'frequency' => $this->frequency,
            'device' => $this->device,
            'browser' => $this->browser,
            'related_url' => $this->related_url,
        ]);

        $attachments->attachMany($ticket, $this->attachments);

        session()->flash('support_status', "Ticket {$ticket->ticket_number} submitted successfully.");
        $this->redirect(route('support.show', $ticket), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesForStep(int $step): array
    {
        if ($this->track === 'enhancement') {
            return $step === 1 ? $this->enhancementRules() : [];
        }

        return match ($step) {
            1 => [
                'type' => ['required', Rule::in(array_keys(config('support.ticket_types', [])))],
                'module' => ['required', Rule::in(array_keys(config('support.modules', [])))],
                'category' => ['required', Rule::in(array_keys(config('support.categories', [])))],
            ],
            2 => [
                'user_intent_action' => ['required', Rule::in(array_keys(config('support.user_intent_actions', [])))],
                'user_reported_outcome' => ['required', Rule::in(array_keys(config('support.user_reported_outcomes', [])))],
                'subject' => ['required', 'string', 'max:100'],
                'description' => ['required', 'string', 'min:20'],
            ],
            3 => [
                'urgency' => ['required', Rule::in(array_keys(config('support.urgency_levels', [])))],
                'impact' => ['required', Rule::in(array_keys(config('support.impact_levels', [])))],
                'frequency' => ['required', Rule::in(array_keys(config('support.frequency_levels', [])))],
                'device' => ['required', Rule::in(array_keys(config('support.devices', [])))],
                'browser' => ['required', Rule::in(array_keys(config('support.browsers', [])))],
                'related_url' => ['nullable', 'url', 'max:2048'],
            ],
            4 => [
                'attachments' => ['array', 'max:5'],
                'attachments.*' => ['file', 'max:10240'],
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function enhancementRules(): array
    {
        return [
            'feature_title' => ['required', 'string', 'max:255'],
            'related_module' => ['required', Rule::in(array_keys(config('support.modules', [])))],
            'problem_solved' => ['required', 'string', 'min:10'],
            'suggested_description' => ['required', 'string', 'min:20'],
            'example_link' => ['nullable', 'url', 'max:2048'],
            'user_priority' => ['required', Rule::in(array_keys(config('support.wishlist_user_priorities', [])))],
            'business_value' => ['array'],
            'business_value.*' => [Rule::in(array_keys(config('support.business_value_options', [])))],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    public function render(): View
    {
        return view('livewire.support.support-ticket-wizard', [
            'ticketTypes' => config('support.ticket_types', []),
            'modules' => config('support.modules', []),
            'categories' => config('support.categories', []),
            'intentActions' => config('support.user_intent_actions', []),
            'outcomes' => config('support.user_reported_outcomes', []),
            'urgencyLevels' => config('support.urgency_levels', []),
            'impactLevels' => config('support.impact_levels', []),
            'frequencyLevels' => config('support.frequency_levels', []),
            'devices' => config('support.devices', []),
            'browsers' => config('support.browsers', []),
            'businessValueOptions' => config('support.business_value_options', []),
            'wishlistPriorities' => config('support.wishlist_user_priorities', []),
            'documentationModules' => config('support-documentation.modules', []),
            'maxStep' => $this->track === 'enhancement' ? 2 : 5,
        ]);
    }
}
