<?php

namespace App\Livewire\Admin\Communication;

use App\Models\AnnouncementNewsletter;
use App\Services\Communication\CommunicationAiAssistantService;
use App\Services\Communication\NewsletterGeneratorService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NewsletterGenerator extends Component
{
    public string $period_type = 'weekly';

    public ?string $custom_start = null;

    public ?string $custom_end = null;

    public string $intro_override = '';

    public string $audience_type = 'all';

    public ?int $previewNewsletterId = null;

    public string $ai_topic = '';

    public string $ai_draft_type = 'announcement';

    /** @var array{title: string, summary: string|null, body: string, source: string}|null */
    public ?array $aiDraft = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('manage newsletters'), 403);
    }

    public function generate(NewsletterGeneratorService $newsletters): void
    {
        abort_unless(auth()->user()?->can('manage newsletters'), 403);

        $this->validate([
            'period_type' => ['required', 'in:'.implode(',', array_keys(config('communication-hub.newsletter_periods', [])))],
            'custom_start' => ['nullable', 'date'],
            'custom_end' => ['nullable', 'date', 'after_or_equal:custom_start'],
        ]);

        $starts = filled($this->custom_start) ? Carbon::parse($this->custom_start)->startOfDay() : null;
        $ends = filled($this->custom_end) ? Carbon::parse($this->custom_end)->endOfDay() : null;

        $newsletter = $newsletters->compile(
            auth()->user(),
            $this->period_type,
            $starts,
            $ends,
            filled(trim($this->intro_override)) ? trim($this->intro_override) : null,
        );

        $this->previewNewsletterId = $newsletter->id;
        session()->flash('communication_admin_status', 'Newsletter compiled and ready to preview.');
    }

    public function sendNewsletter(NewsletterGeneratorService $newsletters): void
    {
        abort_unless(auth()->user()?->can('manage newsletters'), 403);

        if (! $this->previewNewsletterId) {
            $this->addError('previewNewsletterId', 'Generate a newsletter before sending.');

            return;
        }

        $newsletter = AnnouncementNewsletter::query()->findOrFail($this->previewNewsletterId);
        $newsletters->send($newsletter, auth()->user(), $this->audience_type);

        session()->flash('communication_admin_status', 'Newsletter queued for delivery.');
    }

    public function generateAiDraft(CommunicationAiAssistantService $ai): void
    {
        abort_unless(auth()->user()?->can('manage newsletters'), 403);

        $this->validate([
            'ai_draft_type' => ['required', 'string'],
            'ai_topic' => ['required', 'string', 'max:255'],
        ]);

        $this->aiDraft = $ai->generateDraft($this->ai_draft_type, [
            'topic' => $this->ai_topic,
            'author_name' => auth()->user()->name,
            'organization' => config('app.name'),
        ], auth()->user());
    }

    public function applyAiDraftToIntro(): void
    {
        if ($this->aiDraft) {
            $this->intro_override = trim($this->aiDraft['body'] ?: ($this->aiDraft['summary'] ?? ''));
        }
    }

    public function render(NewsletterGeneratorService $newsletters): View
    {
        $preview = $this->previewNewsletterId
            ? AnnouncementNewsletter::query()->find($this->previewNewsletterId)
            : null;

        return view('livewire.admin.communication.newsletter-generator', [
            'periods' => config('communication-hub.newsletter_periods', []),
            'audienceTypes' => config('communication-hub.audience_types', []),
            'aiDraftTypes' => config('communication-hub.ai.draft_types', []),
            'recentNewsletters' => $newsletters->recent(8),
            'preview' => $preview,
        ]);
    }
}
