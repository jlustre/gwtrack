<?php

namespace App\Livewire\Communication;

use App\Models\Badge;
use App\Models\User;
use App\Services\Communication\RecognitionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Post Recognition')]
class RecognitionComposer extends Component
{
    public string $template = 'new_license';

    public ?int $honoree_user_id = null;

    public string $title = '';

    public string $summary = '';

    public string $body = '';

    public ?int $badge_id = null;

    public bool $is_featured = true;

    public bool $publish_now = true;

    public function mount(RecognitionService $recognition): void
    {
        abort_unless(auth()->user()?->can('manage recognition posts') || auth()->user()?->can('manage recognition'), 403);
        $this->applyTemplate($recognition);
    }

    public function updatedTemplate(RecognitionService $recognition): void
    {
        $this->applyTemplate($recognition);
    }

    public function updatedHonoreeUserId(RecognitionService $recognition): void
    {
        $this->applyTemplate($recognition);
    }

    public function save(RecognitionService $recognition): void
    {
        abort_unless(auth()->user()?->can('manage recognition posts') || auth()->user()?->can('manage recognition'), 403);

        $validated = $this->validate([
            'template' => ['required', 'string', 'in:'.implode(',', array_keys(config('communication-hub.recognition_templates', [])))],
            'honoree_user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'badge_id' => ['nullable', 'integer', 'exists:badges,id'],
            'is_featured' => ['boolean'],
            'publish_now' => ['boolean'],
        ]);

        $announcement = $recognition->createRecognitionPost([
            'recognition_type' => $validated['template'],
            'honoree_user_id' => $validated['honoree_user_id'],
            'title' => $validated['title'],
            'summary' => $validated['summary'],
            'body' => $validated['body'],
            'badge_id' => $validated['badge_id'],
            'is_featured' => $validated['is_featured'],
            'audience_type' => 'all',
        ], auth()->user());

        if ($this->publish_now) {
            $this->authorize('publish', $announcement);
            $recognition->publishRecognition($announcement, auth()->user());
        }

        session()->flash('communication_status', $this->publish_now
            ? 'Recognition post published and badge awarded.'
            : 'Recognition post saved as draft.');

        $this->redirectRoute('communications.show', $announcement, navigate: true);
    }

    public function render(RecognitionService $recognition): View
    {
        return view('livewire.communication.recognition-composer', [
            'templates' => $recognition->templates(),
            'honorees' => User::query()->whereNull('deleted_at')->where('is_active', true)->orderBy('name')->limit(200)->get(['id', 'name']),
            'badges' => $recognition->activeBadges(),
        ])->layout('layouts.app');
    }

    private function applyTemplate(RecognitionService $recognition): void
    {
        if (! $this->honoree_user_id) {
            return;
        }

        $honoree = User::query()->find($this->honoree_user_id);

        if (! $honoree) {
            return;
        }

        $rendered = $recognition->renderTemplate($this->template, $honoree);
        $this->title = $rendered['title'];
        $this->summary = $rendered['summary'];
        $this->body = $rendered['body'];

        if ($rendered['badge_slug']) {
            $this->badge_id = Badge::query()->where('slug', $rendered['badge_slug'])->value('id');
        }
    }
}
