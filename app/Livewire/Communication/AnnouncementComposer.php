<?php

namespace App\Livewire\Communication;

use App\Models\AnnouncementCategory;
use App\Services\Communication\CommunicationHubService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Announcement')]
class AnnouncementComposer extends Component
{
    public ?int $category_id = null;

    public string $title = '';

    public string $summary = '';

    public string $body = '';

    public string $priority = 'informational';

    public string $audience_type = 'all';

    public bool $requires_acknowledgement = false;

    public bool $is_pinned = false;

    public bool $publish_now = true;

    public string $ai_topic = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('create', \App\Models\MessageCenterAnnouncement::class), 403);
    }

    public function save(CommunicationHubService $hub): void
    {
        $this->authorize('create', \App\Models\MessageCenterAnnouncement::class);

        $validated = $this->validate([
            'category_id' => ['required', 'integer', 'exists:announcement_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:'.implode(',', array_keys(config('communication-hub.priorities', [])))],
            'audience_type' => ['required', 'string', 'in:'.implode(',', array_keys(config('communication-hub.audience_types', [])))],
            'requires_acknowledgement' => ['boolean'],
            'is_pinned' => ['boolean'],
            'publish_now' => ['boolean'],
        ]);

        $announcement = $hub->createDraft([
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'summary' => $validated['summary'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'audience_type' => $validated['audience_type'],
            'requires_acknowledgement' => $validated['requires_acknowledgement'],
            'is_pinned' => $validated['is_pinned'],
        ], auth()->user());

        if ($this->publish_now) {
            $this->authorize('publish', $announcement);
            $hub->publish($announcement);
        }

        session()->flash('communication_status', $this->publish_now
            ? 'Announcement published and notifications queued.'
            : 'Announcement saved as draft.');

        $this->redirectRoute('communications.show', $announcement, navigate: true);
    }

    public function suggestDraft(\App\Services\Communication\CommunicationAiAssistantService $ai): void
    {
        $this->authorize('create', \App\Models\MessageCenterAnnouncement::class);

        $this->validate([
            'ai_topic' => ['required', 'string', 'max:255'],
        ]);

        $category = $this->category_id
            ? AnnouncementCategory::query()->find($this->category_id)
            : null;

        $draftType = match ($category?->code) {
            'leadership' => 'leadership_message',
            'event' => 'event_summary',
            'campaign' => 'campaign_update',
            default => 'announcement',
        };

        $draft = $ai->generateDraft($draftType, [
            'topic' => $this->ai_topic,
            'author_name' => auth()->user()->name,
            'organization' => config('app.name'),
            'template_code' => match ($draftType) {
                'leadership_message' => 'leadership-weekly',
                'event_summary' => 'event-summary',
                'campaign_update' => 'campaign-update',
                default => 'general-update',
            },
        ], auth()->user());

        $this->title = $draft['title'];
        $this->summary = $draft['summary'] ?? '';
        $this->body = $draft['body'];
    }

    public function render(): View
    {
        return view('livewire.communication.announcement-composer', [
            'categories' => AnnouncementCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'priorities' => config('communication-hub.priorities', []),
            'audienceTypes' => config('communication-hub.audience_types', []),
        ])->layout('layouts.app');
    }
}
