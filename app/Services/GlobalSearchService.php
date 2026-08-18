<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\MessageCenterAnnouncement;
use App\Models\PortalResource;
use App\Models\Prospect;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\TaskUser;
use App\Services\Communication\AnnouncementAudienceResolver;
use App\Support\MemberDisplayName;
use App\Support\ResourceDocumentCategories;
use App\Support\ResourceLinkCategories;
use App\Support\ResourceVideoCategories;
use App\Support\VideoEmbed;
use Illuminate\Support\Str;

class GlobalSearchService
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
        private readonly AnnouncementAudienceResolver $announcementAudience,
    ) {}

    /**
     * @return array{
     *     query: string,
     *     total: int,
     *     sections: list<array{key: string, label: string, count: int, results: list<array<string, mixed>>}>
     * }
     */
    public function search(User $user, ?string $query, ?int $limit = null): array
    {
        $query = trim((string) $query);
        $limit = $limit ?? (int) config('global-search.results_limit', 8);
        $minLength = (int) config('global-search.min_query_length', 2);

        if (mb_strlen($query) < $minLength) {
            return [
                'query' => $query,
                'total' => 0,
                'sections' => [],
            ];
        }

        $sections = [];

        foreach (config('global-search.sections', []) as $key => $section) {
            if (! $this->userCanSearchSection($user, $section['permission'] ?? null)) {
                continue;
            }

            $results = match ($key) {
                'members' => $this->searchMembers($user, $query, $limit),
                'prospects' => $this->searchProspects($user, $query, $limit),
                'resources' => $this->searchResources($user, $query, $limit),
                'videos' => $this->searchVideos($user, $query, $limit),
                'training' => $this->searchTraining($user, $query, $limit),
                'tasks' => $this->searchTasks($user, $query, $limit),
                'events' => $this->searchEvents($user, $query, $limit),
                'announcements' => $this->searchAnnouncements($user, $query, $limit),
                default => [],
            };

            if ($results !== []) {
                $sections[] = [
                    'key' => $key,
                    'label' => $section['label'],
                    'count' => count($results),
                    'results' => $results,
                ];
            }
        }

        return [
            'query' => $query,
            'total' => collect($sections)->sum('count'),
            'sections' => $sections,
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string, type: string}>
     */
    public function suggest(User $user, ?string $query): array
    {
        $payload = $this->search($user, $query, (int) config('global-search.suggest_limit', 5));

        return collect($payload['sections'])
            ->flatMap(fn (array $section) => collect($section['results'])->map(fn (array $result): array => [
                'title' => $result['title'],
                'subtitle' => $result['subtitle'],
                'url' => $result['url'],
                'meta' => $result['meta'] ?? null,
                'type' => $section['label'],
            ]))
            ->take((int) config('global-search.suggest_limit', 5))
            ->values()
            ->all();
    }

    private function userCanSearchSection(User $user, ?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        foreach (explode('|', $permission) as $single) {
            if ($user->hasPermissionTo(trim($single))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string}>
     */
    private function searchMembers(User $user, string $query, int $limit): array
    {
        $term = '%'.$query.'%';

        return $this->hierarchy->visibleMembersQuery($user)
            ->where('users.id', '!=', $user->id)
            ->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            })
            ->with(['rank', 'profile'])
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $member): array => [
                'title' => MemberDisplayName::for($member),
                'subtitle' => $member->rank?->name ?? 'Team member',
                'url' => route('team.member.profile', $member),
                'meta' => $member->email,
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string}>
     */
    private function searchProspects(User $user, string $query, int $limit): array
    {
        $term = '%'.$query.'%';

        return Prospect::query()
            ->whereNull('deleted_at')
            ->where(function ($builder) use ($user): void {
                $builder->where('owner_id', $user->id)
                    ->orWhereHas('shares', function ($shareQuery) use ($user): void {
                        $shareQuery->where('shared_with', $user->id)
                            ->where('status', 'active')
                            ->whereNull('revoked_at')
                            ->where(function ($expiresQuery): void {
                                $expiresQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            });
                    });
            })
            ->where(function ($builder) use ($term): void {
                $builder->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('preferred_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            })
            ->with('stage:id,name')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (Prospect $prospect): array => [
                'title' => $prospect->displayName(),
                'subtitle' => str($prospect->funnel_type)->title().' · '.($prospect->stage?->name ?? 'Pipeline'),
                'url' => route('team.prospects.records.show', $prospect),
                'meta' => $prospect->email ?? $prospect->phone,
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string}>
     */
    private function searchResources(User $user, string $query, int $limit): array
    {
        $term = '%'.$query.'%';

        return PortalResource::query()
            ->where('is_published', true)
            ->whereIn('type', ['document', 'file', 'link'])
            ->where(function ($builder) use ($term): void {
                $builder->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(function (PortalResource $resource): array {
                $route = match ($resource->type) {
                    'link' => route('resources.links', ['search' => $resource->title]),
                    default => route('resources.documents', ['search' => $resource->title]),
                };

                $categoryLabel = match ($resource->type) {
                    'link' => ResourceLinkCategories::label($resource->category ?? 'general'),
                    default => ResourceDocumentCategories::label($resource->category ?? 'general'),
                };

                return [
                    'title' => $resource->title,
                    'subtitle' => str($resource->type)->title().' · '.$categoryLabel,
                    'url' => $route,
                    'meta' => Str::limit(strip_tags((string) $resource->description), 80),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string}>
     */
    private function searchVideos(User $user, string $query, int $limit): array
    {
        $term = '%'.$query.'%';

        return PortalResource::query()
            ->where('type', 'video')
            ->where('is_published', true)
            ->where(function ($builder) use ($term): void {
                $builder->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (PortalResource $video): array => [
                'title' => $video->title,
                'subtitle' => 'Video · '.ResourceVideoCategories::label($video->category ?? 'general'),
                'url' => route('resources.videos', ['search' => $video->title, 'video' => $video->id]),
                'meta' => Str::limit(strip_tags((string) $video->description), 80),
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string}>
     */
    private function searchTraining(User $user, string $query, int $limit): array
    {
        $term = '%'.$query.'%';

        return TrainingModule::query()
            ->published()
            ->where(function ($builder) use ($term): void {
                $builder->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            })
            ->with('category:id,name')
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (TrainingModule $module): array => [
                'title' => $module->title,
                'subtitle' => $module->category?->name ?? 'Training course',
                'url' => route('training.courses.show', $module),
                'meta' => Str::limit(strip_tags((string) $module->description), 80),
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string}>
     */
    private function searchTasks(User $user, string $query, int $limit): array
    {
        $term = '%'.$query.'%';

        return TaskUser::query()
            ->with(['taskCategory', 'task'])
            ->openForUser($user)
            ->where(function ($builder) use ($term): void {
                $builder->where('additional_notes', 'like', $term)
                    ->orWhere('related_person', 'like', $term)
                    ->orWhereHas('task', fn ($taskQuery) => $taskQuery
                        ->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term))
                    ->orWhereHas('taskCategory', fn ($categoryQuery) => $categoryQuery->where('name', 'like', $term));
            })
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('due_date')
            ->limit($limit)
            ->get()
            ->map(fn (TaskUser $task): array => [
                'title' => $task->displayTitle(),
                'subtitle' => ($task->categoryName() ?? 'Task').' · '.$task->displayStatus(),
                'url' => route('tasks.index', ['q' => $task->displayTitle()]),
                'meta' => $task->due_date?->format('M j, Y'),
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string}>
     */
    private function searchEvents(User $user, string $query, int $limit): array
    {
        $term = '%'.$query.'%';

        return CalendarEvent::query()
            ->where(function ($builder) use ($user): void {
                $builder->where('organizer_id', $user->id)
                    ->orWhereHas('attendees', fn ($attendeeQuery) => $attendeeQuery->where('user_id', $user->id));
            })
            ->where(function ($builder) use ($term): void {
                $builder->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('location', 'like', $term);
            })
            ->orderByDesc('starts_at')
            ->limit($limit)
            ->get()
            ->map(fn (CalendarEvent $event): array => [
                'title' => $event->title,
                'subtitle' => $event->starts_at?->format('M j, Y g:i A') ?? 'Scheduled event',
                'url' => route('calendar.events.show', $event),
                'meta' => $event->location,
            ])
            ->all();
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, meta: ?string}>
     */
    private function searchAnnouncements(User $user, string $query, int $limit): array
    {
        $term = '%'.$query.'%';

        return MessageCenterAnnouncement::query()
            ->published()
            ->visible()
            ->where(function ($builder) use ($term): void {
                $builder->where('title', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhere('body', 'like', $term);
            })
            ->latest('published_at')
            ->limit($limit * 3)
            ->get()
            ->filter(fn (MessageCenterAnnouncement $announcement): bool => $this->announcementAudience->userCanSee($user, $announcement))
            ->take($limit)
            ->map(fn (MessageCenterAnnouncement $announcement): array => [
                'title' => $announcement->title,
                'subtitle' => 'Announcement · '.str($announcement->priority)->title(),
                'url' => route('communications.show', $announcement),
                'meta' => $announcement->published_at?->format('M j, Y'),
            ])
            ->values()
            ->all();
    }
}
