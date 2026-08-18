<?php

namespace App\Services\TodaysAgenda\Providers;

use App\Contracts\TodaysAgendaSectionProvider;
use App\Models\ScheduledPhoneCall;
use App\Models\User;
use App\Services\ScheduledPhoneCallService;
use App\Services\TodaysAgenda\Support\AgendaSectionBuilder;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class PhoneCallsSectionProvider implements TodaysAgendaSectionProvider
{
    use AgendaSectionBuilder;

    public function __construct(
        private readonly ScheduledPhoneCallService $phoneCalls,
    ) {}

    public function key(): string
    {
        return 'phone_calls';
    }

    public function label(): string
    {
        return 'Phone Calls';
    }

    public function icon(): string
    {
        return 'phone';
    }

    public function sort(): int
    {
        return 20;
    }

    public function isEnabledFor(User $user): bool
    {
        return true;
    }

    public function sectionFor(User $user, CarbonInterface $dayStart, CarbonInterface $dayEnd): array
    {
        $panels = $this->phoneCalls->upcomingPanelsFor($user, limit: 50);

        $items = collect(['follow_up', 'today', 'tomorrow', 'later'])
            ->flatMap(function (string $bucket) use ($panels): Collection {
                return collect($panels[$bucket] ?? collect())
                    ->map(fn (ScheduledPhoneCall $call): array => $this->mapCall($call, $bucket));
            })
            ->values()
            ->all();

        return $this->buildSection(
            $this->key(),
            $this->label(),
            $this->icon(),
            $items,
            'No scheduled phone calls.',
            ['label' => 'Manage phone calls', 'url' => '#schedule-phone-call'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCall(ScheduledPhoneCall $call, string $bucket): array
    {
        $url = $call->prospect_id && Route::has('team.prospects.records.show')
            ? route('team.prospects.records.show', $call->prospect_id)
            : null;

        $scheduledAt = $call->scheduled_at->timezone($call->timezone ?? config('app.timezone'));

        return [
            'id' => 'phone-call-'.$call->id,
            'title' => $call->contact_name,
            'subtitle' => filled($call->purpose) ? $call->purpose : $call->personTypeLabel(),
            'meta' => $scheduledAt->format('M j · g:i A'),
            'url' => $url,
            'badge' => match ($bucket) {
                'follow_up' => 'Overdue',
                'today' => 'Today',
                'tomorrow' => 'Tomorrow',
                default => 'Upcoming',
            },
            'highlight' => in_array($bucket, ['follow_up', 'today'], true),
            'sort_at' => $call->scheduled_at->toIso8601String(),
        ];
    }
}
