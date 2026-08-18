<?php

namespace App\Services\TodaysAgenda\Providers;

use App\Contracts\TodaysAgendaSectionProvider;
use App\Models\ProspectAppointment;
use App\Models\User;
use App\Services\TodaysAgenda\Support\AgendaSectionBuilder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;

class AppointmentsSectionProvider implements TodaysAgendaSectionProvider
{
    use AgendaSectionBuilder;

    private const ITEM_LIMIT = 50;

    public function key(): string
    {
        return 'appointments';
    }

    public function label(): string
    {
        return 'Appointments';
    }

    public function icon(): string
    {
        return 'calendar-clock';
    }

    public function sort(): int
    {
        return 30;
    }

    public function isEnabledFor(User $user): bool
    {
        return $user->can('manage prospects') && Route::has('team.prospects.appointments');
    }

    public function sectionFor(User $user, CarbonInterface $dayStart, CarbonInterface $dayEnd): array
    {
        $appointments = ProspectAppointment::query()
            ->with(['prospect:id,first_name,last_name,preferred_name', 'type:id,name,slug'])
            ->where('owner_id', $user->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', $dayStart)
            ->orderBy('scheduled_at')
            ->limit(self::ITEM_LIMIT)
            ->get();

        $items = $appointments
            ->map(function (ProspectAppointment $appointment): array {
                $prospectName = filled($appointment->prospect?->preferred_name)
                    ? $appointment->prospect->preferred_name
                    : trim(($appointment->prospect?->first_name ?? '').' '.($appointment->prospect?->last_name ?? ''));

                return [
                    'id' => 'appointment-'.$appointment->id,
                    'title' => filled($prospectName) ? $prospectName : 'CRM appointment',
                    'subtitle' => $appointment->type?->name ?? 'Appointment',
                    'meta' => $appointment->scheduled_at->format('M j · g:i A'),
                    'url' => $appointment->prospect_id && Route::has('team.prospects.records.show')
                        ? route('team.prospects.records.show', $appointment->prospect_id)
                        : route('team.prospects.appointments'),
                    'badge' => $appointment->scheduled_at->isToday() ? 'Today' : 'Scheduled',
                    'highlight' => $appointment->scheduled_at->isToday(),
                    'sort_at' => $appointment->scheduled_at->toIso8601String(),
                ];
            })
            ->all();

        return $this->buildSection(
            $this->key(),
            $this->label(),
            $this->icon(),
            $items,
            'No upcoming CRM appointments.',
            ['label' => 'View appointments', 'url' => route('team.prospects.appointments')],
        );
    }
}
