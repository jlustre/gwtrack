<?php

namespace App\Services\Communication;

use App\Models\AnnouncementAcknowledgement;
use App\Models\AnnouncementAnalyticsDaily;
use App\Models\AnnouncementBookmark;
use App\Models\AnnouncementCampaign;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementReaction;
use App\Models\BroadcastMessage;
use App\Models\MessageCenterAnnouncement;
use App\Models\MessageCenterAnnouncementRead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class AnnouncementAnalyticsService
{
    public function __construct(
        private readonly AnnouncementAudienceResolver $audience,
        private readonly AnnouncementAcknowledgementService $acknowledgements,
    ) {}

    /**
     * @return array<string, int|float>
     */
    public function dashboardMetrics(): array
    {
        $published = MessageCenterAnnouncement::query()->where('status', 'published');
        $active = (clone $published)->visible()->count();
        $critical = (clone $published)->visible()
            ->where('requires_acknowledgement', true)
            ->whereIn('priority', ['critical', 'emergency'])
            ->count();

        $ackReport = $this->acknowledgements->acknowledgementReport();
        $pendingAcks = collect($ackReport)->sum('pending_count');

        return [
            'total_announcements' => MessageCenterAnnouncement::query()->count(),
            'active_announcements' => $active,
            'critical_announcements' => $critical,
            'pending_acknowledgements' => (int) $pendingAcks,
            'total_views' => (int) MessageCenterAnnouncement::query()->sum('view_count'),
            'total_reactions' => AnnouncementReaction::query()->count(),
            'total_comments' => AnnouncementComment::query()->count(),
            'total_bookmarks' => AnnouncementBookmark::query()->count(),
            'campaigns_running' => AnnouncementCampaign::query()
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->count(),
            'broadcasts_sent' => BroadcastMessage::query()->where('status', 'sent')->count(),
        ];
    }

    /**
     * @return list<array{title: string, slug: string, views: int, engagement: int, score: float}>
     */
    public function topAnnouncements(int $limit = 5): array
    {
        return MessageCenterAnnouncement::query()
            ->published()
            ->withCount(['reactions', 'comments', 'bookmarks'])
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get()
            ->map(fn (MessageCenterAnnouncement $announcement) => [
                'title' => $announcement->title,
                'slug' => $announcement->slug,
                'views' => (int) $announcement->view_count,
                'engagement' => (int) $announcement->reactions_count + (int) $announcement->comments_count + (int) $announcement->bookmarks_count,
                'score' => (float) $announcement->view_count + ((int) $announcement->reactions_count * 2) + ((int) $announcement->comments_count * 3),
            ])
            ->values()
            ->all();
    }

    public function rollupForDate(Carbon $date): int
    {
        $count = 0;
        $statDate = $date->toDateString();

        $announcements = MessageCenterAnnouncement::query()
            ->published()
            ->get(['id']);

        foreach ($announcements as $announcement) {
            $reads = MessageCenterAnnouncementRead::query()
                ->where('announcement_id', $announcement->id)
                ->whereDate('read_at', $statDate)
                ->count();
            $acks = AnnouncementAcknowledgement::query()
                ->where('announcement_id', $announcement->id)
                ->whereDate('acknowledged_at', $statDate)
                ->count();
            $reactions = AnnouncementReaction::query()
                ->where('announcement_id', $announcement->id)
                ->whereDate('created_at', $statDate)
                ->count();
            $comments = AnnouncementComment::query()
                ->where('announcement_id', $announcement->id)
                ->whereDate('created_at', $statDate)
                ->count();
            $bookmarks = AnnouncementBookmark::query()
                ->where('announcement_id', $announcement->id)
                ->whereDate('created_at', $statDate)
                ->count();

            if ($reads + $acks + $reactions + $comments + $bookmarks === 0) {
                continue;
            }

            AnnouncementAnalyticsDaily::query()->updateOrCreate(
                [
                    'stat_date' => $statDate,
                    'announcement_id' => $announcement->id,
                ],
                [
                    'views' => $reads,
                    'reads' => $reads,
                    'acknowledgements' => $acks,
                    'reactions' => $reactions,
                    'comments' => $comments,
                    'bookmarks' => $bookmarks,
                    'reach' => $reads,
                ],
            );

            $count++;
        }

        return $count;
    }

    /**
     * @return Collection<int, AnnouncementAnalyticsDaily>
     */
    public function trend(int $days = 14): Collection
    {
        return AnnouncementAnalyticsDaily::query()
            ->selectRaw('stat_date, SUM(views) as views, SUM(reactions) as reactions, SUM(comments) as comments')
            ->where('stat_date', '>=', now()->subDays($days)->toDateString())
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function archiveFor(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        $items = MessageCenterAnnouncement::query()
            ->where(function ($query): void {
                $query->where('status', 'archived')
                    ->orWhere(function ($inner): void {
                        $inner->where('status', 'published')
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now());
                    });
            })
            ->with(['category', 'creator', 'campaign'])
            ->when(filled($filters['category_id'] ?? null), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(filled($filters['priority'] ?? null), fn ($query) => $query->where('priority', $filters['priority']))
            ->when(filled($filters['author_id'] ?? null), fn ($query) => $query->where('created_by', $filters['author_id']))
            ->when(filled($filters['campaign_id'] ?? null), fn ($query) => $query->where('campaign_id', $filters['campaign_id']))
            ->when(filled($filters['year'] ?? null), fn ($query) => $query->whereYear('published_at', $filters['year']))
            ->when(filled($filters['month'] ?? null), fn ($query) => $query->whereMonth('published_at', $filters['month']))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $term = '%'.$filters['search'].'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', $term)
                        ->orWhere('summary', 'like', $term)
                        ->orWhere('body', 'like', $term);
                });
            })
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement) => $this->audience->userCanSee($user, $announcement))
            ->values();

        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }
}
