<?php

namespace App\Livewire\Admin\Communication;

use App\Services\Communication\BroadcastService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BroadcastCenter extends Component
{
    public string $title = '';

    public string $body = '';

    public string $priority = 'important';

    public string $audience_type = 'all';

    public int $previewCount = 0;

    public function mount(BroadcastService $broadcasts): void
    {
        abort_unless(auth()->user()?->can('send broadcast'), 403);
        $this->loadPreviewCount($broadcasts);
    }

    public function updatedAudienceType(BroadcastService $broadcasts): void
    {
        $this->loadPreviewCount($broadcasts);
    }

    private function loadPreviewCount(BroadcastService $broadcasts): void
    {
        $this->previewCount = $broadcasts->previewAudienceCount($this->audience_type);
    }

    public function send(BroadcastService $broadcasts): void
    {
        abort_unless(auth()->user()?->can('send broadcast'), 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:'.implode(',', array_keys(config('communication-hub.priorities', [])))],
            'audience_type' => ['required', 'string', 'in:'.implode(',', array_keys(config('communication-hub.audience_types', [])))],
        ]);

        $broadcasts->send(auth()->user(), $validated);

        session()->flash('communication_admin_status', 'Broadcast sent to '.$this->previewCount.' recipients.');

        $this->reset(['title', 'body']);
        $this->priority = 'important';
        $this->audience_type = 'all';
        $this->loadPreviewCount($broadcasts);
    }

    public function render(BroadcastService $broadcasts): View
    {
        return view('livewire.admin.communication.broadcast-center', [
            'priorities' => config('communication-hub.priorities', []),
            'audienceTypes' => config('communication-hub.audience_types', []),
            'recentBroadcasts' => $broadcasts->recent(10),
        ]);
    }
}
