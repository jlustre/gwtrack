<?php

namespace App\Livewire\Dashboard;

use App\Models\ProspectAppointment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UpcomingAppointmentsPanel extends Component
{
    public bool $compact = false;

    public function render(): View
    {
        $viewer = auth()->user();

        $appointments = $viewer
            ? ProspectAppointment::query()
                ->with(['prospect', 'type'])
                ->where('owner_id', $viewer->id)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now()->startOfDay())
                ->orderBy('scheduled_at')
                ->limit($this->compact ? 8 : 15)
                ->get()
            : collect();

        return view('livewire.dashboard.upcoming-appointments-panel', [
            'appointments' => $appointments,
        ]);
    }
}
