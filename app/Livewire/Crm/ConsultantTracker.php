<?php

namespace App\Livewire\Crm;

use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\User;
use App\Services\Crm\CrmFunnelService;
use App\Services\DownlineHierarchyService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class ConsultantTracker extends Component
{
    #[Url]
    public ?int $memberId = null;

    public string $q = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('view crm funnels'), 403);
    }

    public function selectMember(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function enroll(string $pipelineKey, CrmFunnelService $funnels): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);
        abort_unless($this->memberId, 422);

        $member = User::query()->findOrFail($this->memberId);
        $pipeline = $funnels->pipelineByKey($pipelineKey);
        $funnels->enroll($member, $pipeline, owner: auth()->user(), actor: auth()->user());
    }

    public function moveStage(string $pipelineKey, int $stageId, CrmFunnelService $funnels): void
    {
        abort_unless(auth()->user()?->can('manage crm funnels'), 403);
        abort_unless($this->memberId, 422);

        $pipeline = $funnels->pipelineByKey($pipelineKey);
        $participation = $pipeline->participations()->where('user_id', $this->memberId)->firstOrFail();
        $stage = CrmPipelineStage::query()
            ->where('crm_pipeline_id', $pipeline->id)
            ->whereKey($stageId)
            ->firstOrFail();

        $funnels->moveToStage($participation, $stage, auth()->user(), 'Updated from consultant tracker');
    }

    public function render(CrmFunnelService $funnels, DownlineHierarchyService $hierarchy): View
    {
        $viewer = auth()->user();
        $membersQuery = $hierarchy->visibleMembersQuery($viewer)
            ->orderBy('name')
            ->when(filled($this->q), function ($query): void {
                $term = '%'.strtolower(trim($this->q)).'%';
                $query->where(function ($query) use ($term): void {
                    $query->whereRaw('LOWER(name) like ?', [$term])
                        ->orWhereRaw('LOWER(email) like ?', [$term]);
                });
            });

        $members = (clone $membersQuery)->limit(40)->get(['id', 'name', 'email']);
        $overview = null;

        if ($this->memberId) {
            $member = User::query()->find($this->memberId);
            if ($member && $hierarchy->canViewMember($viewer, $member)) {
                $overview = $funnels->consultantOverview($member->loadMissing('profile'));
            } else {
                $this->memberId = null;
            }
        }

        $pipelineStages = CrmPipeline::query()
            ->where('is_active', true)
            ->with(['stages' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');

        return view('livewire.crm.consultant-tracker', [
            'members' => $members,
            'overview' => $overview,
            'pipelineStages' => $pipelineStages,
        ])->layout('layouts.app');
    }
}
