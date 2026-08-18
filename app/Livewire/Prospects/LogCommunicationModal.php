<?php

namespace App\Livewire\Prospects;

use App\Models\Prospect;
use App\Services\Prospects\ProspectFunnelService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class LogCommunicationModal extends Component
{
    public bool $show = false;

    public ?string $prospectId = null;

    public ?int $communication_type_id = null;

    public string $direction = 'outbound';

    public string $contacted_at = '';

    public ?string $outcome = null;

    public ?string $notes = null;

    public ?string $next_action = null;

    public ?string $next_follow_up_at = null;

    public ?int $duration_minutes = null;

    #[On('open-log-communication-modal')]
    public function open(string $prospectId): void
    {
        $prospect = Prospect::query()->findOrFail($prospectId);
        $this->authorize('update', $prospect);

        $this->prospectId = $prospectId;
        $this->contacted_at = now()->format('Y-m-d\TH:i');
        $this->reset(['communication_type_id', 'outcome', 'notes', 'next_action', 'next_follow_up_at', 'duration_minutes']);
        $this->direction = 'outbound';
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->prospectId = null;
    }

    public function save(ProspectFunnelService $funnels): void
    {
        $prospect = Prospect::query()->findOrFail($this->prospectId);
        $this->authorize('update', $prospect);

        $validated = $this->validate([
            'communication_type_id' => ['nullable', 'exists:communication_types,id'],
            'direction' => ['required', 'in:inbound,outbound'],
            'contacted_at' => ['required', 'date'],
            'outcome' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'next_action' => ['nullable', 'string', 'max:5000'],
            'next_follow_up_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
        ]);

        $funnels->logCommunication($prospect, auth()->user(), $validated);

        $this->close();
        $this->dispatch('prospect-timeline-refresh');
        $this->dispatch('prospect-board-refresh');
    }

    public function render(): View
    {
        return view('livewire.prospects.log-communication-modal', [
            'communicationTypes' => DB::table('communication_types')->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
