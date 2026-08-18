<?php

namespace App\Livewire\Fna;

use App\Models\FnaRecord;
use App\Services\Fna\FnaAiAssistantService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FnaMeetingPrepPanel extends Component
{
    public FnaRecord $fna;

    public function mount(FnaRecord $fna): void
    {
        $this->authorize('view', $fna);
        $this->fna = $fna;
    }

    public function render(FnaAiAssistantService $ai): View
    {
        $eligibleStatuses = ['approved_by_cfm', 'scheduled_for_client_review', 'presented_to_prospect'];
        $show = $ai->isEnabled('meeting_prep')
            && in_array($this->fna->status, $eligibleStatuses, true);

        return view('livewire.fna.fna-meeting-prep-panel', [
            'show' => $show,
            'talkingPoints' => $show ? $ai->meetingTalkingPoints($this->fna, auth()->user()) : [],
            'complianceNotice' => $ai->complianceNotice(),
        ]);
    }
}
