<?php

namespace App\Livewire\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmRecognitionAward;
use App\Models\CfmEffectiveness\CfmRecognitionBadge;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CfmRecognitionWall extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('view CFM effectiveness'), 403);
    }

    public function render(): View
    {
        return view('livewire.cfm-effectiveness.recognition-wall', [
            'badges' => CfmRecognitionBadge::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'recentAwards' => CfmRecognitionAward::query()
                ->with(['badge', 'cfm.profile'])
                ->latest()
                ->limit(24)
                ->get(),
        ]);
    }
}
