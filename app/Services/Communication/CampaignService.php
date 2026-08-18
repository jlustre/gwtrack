<?php

namespace App\Services\Communication;

use App\Models\AnnouncementCampaign;
use App\Models\AnnouncementCampaignParticipant;
use App\Models\ChecklistProgress;
use App\Models\MemberProductionEntry;
use App\Models\MessageCenterAnnouncement;
use App\Models\TrainingProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CampaignService
{
    public function __construct(
        private readonly AnnouncementAudienceResolver $audience,
    ) {}

    /**
     * @return Collection<int, AnnouncementCampaign>
     */
    public function activeCampaignsFor(User $user): Collection
    {
        return AnnouncementCampaign::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('starts_at')
            ->get()
            ->filter(fn (AnnouncementCampaign $campaign) => $this->userCanParticipate($user, $campaign))
            ->values();
    }

    public function userCanParticipate(User $user, AnnouncementCampaign $campaign): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCampaign(array $data, User $author): AnnouncementCampaign
    {
        $type = $data['type'] ?? 'production';
        $typeConfig = config("communication-hub.campaign_types.{$type}", []);
        $name = $data['name'];
        $code = $data['code'] ?? Str::slug($name);

        return AnnouncementCampaign::query()->create([
            'code' => $this->uniqueCode($code),
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'type' => $type,
            'description' => $data['description'] ?? null,
            'rules' => $data['rules'] ?? null,
            'prizes' => $data['prizes'] ?? [],
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'leaderboard_metric' => $data['leaderboard_metric'] ?? ($typeConfig['metric'] ?? 'production'),
            'leaderboard_config' => $data['leaderboard_config'] ?? null,
            'created_by' => $author->id,
        ]);
    }

    public function joinCampaign(AnnouncementCampaign $campaign, User $user): AnnouncementCampaignParticipant
    {
        $participant = AnnouncementCampaignParticipant::query()->firstOrCreate(
            [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
            ],
            ['joined_at' => now()],
        );

        return $this->syncParticipantProgress($participant);
    }

    public function syncParticipantProgress(AnnouncementCampaignParticipant $participant): AnnouncementCampaignParticipant
    {
        $campaign = $participant->campaign;
        $user = $participant->user;

        if (! $campaign || ! $user) {
            return $participant;
        }

        $value = $this->metricValueForUser($campaign, $user);

        $participant->update([
            'progress_value' => $value,
            'progress_meta' => [
                'metric' => $campaign->leaderboard_metric,
                'synced_at' => now()->toIso8601String(),
            ],
        ]);

        return $participant->fresh(['user', 'campaign']);
    }

    /**
     * @return list<array{rank: int, user_id: int, name: string, progress_value: float, metric: string}>
     */
    public function leaderboard(AnnouncementCampaign $campaign, int $limit = 10): array
    {
        $participants = AnnouncementCampaignParticipant::query()
            ->with('user')
            ->where('campaign_id', $campaign->id)
            ->get()
            ->map(function (AnnouncementCampaignParticipant $participant) use ($campaign) {
                $participant = $this->syncParticipantProgress($participant);

                return $participant;
            })
            ->sortByDesc('progress_value')
            ->values()
            ->take($limit);

        return $participants->values()->map(function (AnnouncementCampaignParticipant $participant, int $index) use ($campaign) {
            return [
                'rank' => $index + 1,
                'user_id' => $participant->user_id,
                'name' => $participant->user?->name ?? 'Member',
                'progress_value' => (float) $participant->progress_value,
                'metric' => $campaign->leaderboard_metric,
            ];
        })->all();
    }

    public function participantFor(User $user, AnnouncementCampaign $campaign): ?AnnouncementCampaignParticipant
    {
        return AnnouncementCampaignParticipant::query()
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function linkAnnouncement(MessageCenterAnnouncement $announcement, AnnouncementCampaign $campaign): MessageCenterAnnouncement
    {
        $announcement->update(['campaign_id' => $campaign->id]);

        return $announcement->fresh();
    }

    private function metricValueForUser(AnnouncementCampaign $campaign, User $user): float
    {
        $start = $campaign->starts_at ?? now()->subYear();
        $end = $campaign->ends_at ?? now();

        return match ($campaign->leaderboard_metric) {
            'recruiting' => (float) User::query()
                ->where('sponsor_id', $user->id)
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'production' => (float) MemberProductionEntry::query()
                ->where('user_id', $user->id)
                ->where('status', 'posted')
                ->whereBetween('posted_at', [$start->toDateString(), $end->toDateString()])
                ->sum('annual_premium'),
            'licensing' => (float) ChecklistProgress::query()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->where(function ($query) use ($start, $end): void {
                    $query->whereBetween('completed_at', [$start, $end])
                        ->orWhere(function ($inner) use ($start, $end): void {
                            $inner->whereNull('completed_at')->whereBetween('updated_at', [$start, $end]);
                        });
                })
                ->count(),
            'training' => (float) TrainingProgress::query()
                ->where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$start, $end])
                ->count(),
            default => 0.0,
        };
    }

    private function uniqueCode(string $code): string
    {
        $base = Str::slug($code) ?: 'campaign';
        $candidate = $base;
        $counter = 1;

        while (AnnouncementCampaign::query()->where('code', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'campaign';
        $slug = $base;
        $counter = 1;

        while (
            AnnouncementCampaign::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
