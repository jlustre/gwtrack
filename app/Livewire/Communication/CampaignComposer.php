<?php

namespace App\Livewire\Communication;

use App\Services\Communication\CampaignService;
use App\Services\Communication\CommunicationHubService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Campaign')]
class CampaignComposer extends Component
{
    public string $name = '';

    public string $type = 'production';

    public string $description = '';

    public string $rules = '';

    public string $prizes = '';

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public bool $publish_announcement = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('manage campaigns'), 403);
        $this->starts_at = now()->toDateString();
        $this->ends_at = now()->addMonth()->toDateString();
    }

    public function save(CampaignService $campaigns, CommunicationHubService $hub): void
    {
        abort_unless(auth()->user()?->can('manage campaigns'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(config('communication-hub.campaign_types', [])))],
            'description' => ['nullable', 'string'],
            'rules' => ['nullable', 'string'],
            'prizes' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $prizeLines = collect(preg_split('/\r\n|\r|\n/', $validated['prizes'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $campaign = $campaigns->createCampaign([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'rules' => $validated['rules'],
            'prizes' => $prizeLines,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ], auth()->user());

        if ($this->publish_announcement) {
            $announcement = $hub->createDraft([
                'category_code' => 'campaign',
                'title' => $campaign->name.' is now live',
                'summary' => $campaign->description,
                'body' => trim(($campaign->rules ?? '')."\n\n".collect($prizeLines)->map(fn ($p) => '• '.$p)->implode("\n")),
                'priority' => 'high',
                'audience_type' => 'all',
                'is_featured' => true,
                'campaign_id' => $campaign->id,
            ], auth()->user());

            $this->authorize('publish', $announcement);
            $hub->publish($announcement);
        }

        session()->flash('communication_status', 'Campaign created successfully.');

        $this->redirectRoute('communications.campaigns.show', $campaign, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.communication.campaign-composer', [
            'campaignTypes' => config('communication-hub.campaign_types', []),
        ])->layout('layouts.app');
    }
}
