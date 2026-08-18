<?php

namespace App\Services\Communication;

use App\Jobs\Communication\SendNewsletterJob;
use App\Models\AnnouncementCampaign;
use App\Models\AnnouncementNewsletter;
use App\Models\MessageCenterAnnouncement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NewsletterGeneratorService
{
    public function __construct(
        private readonly CommunicationAiAssistantService $ai,
    ) {}

    /**
     * @return array{starts: Carbon, ends: Carbon, label: string}
     */
    public function resolvePeriod(string $periodType, ?Carbon $reference = null): array
    {
        $reference = $reference ?? now();

        return match ($periodType) {
            'weekly' => [
                'starts' => $reference->copy()->startOfWeek(),
                'ends' => $reference->copy()->endOfWeek(),
                'label' => 'Week of '.$reference->copy()->startOfWeek()->format('M j, Y'),
            ],
            'monthly' => [
                'starts' => $reference->copy()->startOfMonth(),
                'ends' => $reference->copy()->endOfMonth(),
                'label' => $reference->copy()->format('F Y'),
            ],
            'quarterly' => [
                'starts' => $reference->copy()->firstOfQuarter(),
                'ends' => $reference->copy()->lastOfQuarter(),
                'label' => 'Q'.$reference->quarter.' '.$reference->year,
            ],
            default => throw new \InvalidArgumentException('Unknown newsletter period type.'),
        };
    }

    /**
     * @return array<string, list<array{title: string, summary: string|null, slug: string, published_at: string|null, url: string}>>
     */
    public function compileSections(Carbon $starts, Carbon $ends): array
    {
        $announcements = MessageCenterAnnouncement::query()
            ->published()
            ->whereBetween('published_at', [$starts, $ends])
            ->with(['category', 'campaign', 'calendarEvent'])
            ->orderByDesc('published_at')
            ->get();

        $sections = [
            'leadership' => [],
            'announcements' => [],
            'recognition' => [],
            'events' => [],
            'campaigns' => [],
        ];

        foreach ($announcements as $announcement) {
            $item = $this->formatItem($announcement);
            $code = $announcement->category?->code;

            if ($code === 'leadership') {
                $sections['leadership'][] = $item;
            } elseif ($code === 'recognition') {
                $sections['recognition'][] = $item;
            } elseif ($code === 'event' || $announcement->calendar_event_id) {
                $sections['events'][] = $item;
            } elseif ($code === 'campaign' || $announcement->campaign_id) {
                $sections['campaigns'][] = $item;
            } else {
                $sections['announcements'][] = $item;
            }
        }

        $activeCampaigns = AnnouncementCampaign::query()
            ->where('is_active', true)
            ->where(function ($query) use ($starts, $ends): void {
                $query->whereBetween('starts_at', [$starts, $ends])
                    ->orWhereBetween('ends_at', [$starts, $ends])
                    ->orWhere(function ($inner) use ($starts, $ends): void {
                        $inner->where('starts_at', '<=', $starts)
                            ->where(function ($range) use ($ends): void {
                                $range->whereNull('ends_at')->orWhere('ends_at', '>=', $ends);
                            });
                    });
            })
            ->orderByDesc('starts_at')
            ->get();

        foreach ($activeCampaigns as $campaign) {
            $sections['campaigns'][] = [
                'title' => $campaign->name,
                'summary' => $campaign->description,
                'slug' => $campaign->slug,
                'published_at' => $campaign->starts_at?->toDateTimeString(),
                'url' => route('communications.campaigns.show', $campaign),
            ];
        }

        return $sections;
    }

    public function compile(
        User $author,
        string $periodType,
        ?Carbon $starts = null,
        ?Carbon $ends = null,
        ?string $introOverride = null,
    ): AnnouncementNewsletter {
        $period = $this->resolvePeriod($periodType);
        $starts = $starts ?? $period['starts'];
        $ends = $ends ?? $period['ends'];

        $sections = $this->compileSections($starts, $ends);
        $intro = $introOverride ?? $this->generateIntro($periodType, $period['label'], $sections);
        $title = config("communication-hub.newsletter_periods.{$periodType}.label", ucfirst($periodType).' Newsletter')
            .' — '.$period['label'];

        $htmlBody = $this->renderHtml($title, $intro, $sections, $period['label']);
        $textBody = $this->renderText($title, $intro, $sections);

        $announcementIds = MessageCenterAnnouncement::query()
            ->published()
            ->whereBetween('published_at', [$starts, $ends])
            ->pluck('id')
            ->all();

        return AnnouncementNewsletter::query()->create([
            'title' => $title,
            'slug' => $this->uniqueSlug($title),
            'period_type' => $periodType,
            'period_starts_at' => $starts,
            'period_ends_at' => $ends,
            'status' => 'ready',
            'subject' => $title,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
            'compiled_sections' => $sections,
            'announcement_ids' => $announcementIds,
            'created_by' => $author->id,
            'metadata' => [
                'intro' => $intro,
                'period_label' => $period['label'],
            ],
        ]);
    }

    public function send(AnnouncementNewsletter $newsletter, User $sender, string $audienceType = 'all'): AnnouncementNewsletter
    {
        SendNewsletterJob::dispatch($newsletter->id, $sender->id, $audienceType);

        return $newsletter->fresh();
    }

    public function markSent(AnnouncementNewsletter $newsletter, int $recipientCount): AnnouncementNewsletter
    {
        $newsletter->forceFill([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_count' => $recipientCount,
        ])->save();

        return $newsletter->fresh();
    }

    /**
     * @return Collection<int, AnnouncementNewsletter>
     */
    public function recent(int $limit = 10): Collection
    {
        return AnnouncementNewsletter::query()
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, list<array{title: string, summary: string|null, slug: string, published_at: string|null, url: string}>>  $sections
     */
    private function generateIntro(string $periodType, string $periodLabel, array $sections): string
    {
        $counts = collect($sections)->map(fn (array $items) => count($items))->sum();

        $draft = $this->ai->generateDraft('newsletter_intro', [
            'template_code' => 'newsletter-intro-weekly',
            'period_type' => $periodType,
            'period_label' => $periodLabel,
            'item_count' => (string) $counts,
            'leadership_count' => (string) count($sections['leadership']),
            'recognition_count' => (string) count($sections['recognition']),
            'event_count' => (string) count($sections['events']),
            'campaign_count' => (string) count($sections['campaigns']),
            'organization' => config('app.name'),
        ]);

        return trim($draft['body'] !== '' ? $draft['body'] : ($draft['summary'] ?? ''));
    }

    /**
     * @param  array<string, list<array{title: string, summary: string|null, slug: string, published_at: string|null, url: string}>>  $sections
     */
    private function renderHtml(string $title, string $intro, array $sections, string $periodLabel): string
    {
        $sectionLabels = config('communication-hub.newsletter_sections', []);
        $html = '<div style="font-family:Georgia,serif;color:#2D3E50;max-width:640px;margin:0 auto;">';
        $html .= '<div style="background:#2D3E50;color:#84BB41;padding:24px;text-align:center;">';
        $html .= '<p style="margin:0;font-size:12px;letter-spacing:0.2em;text-transform:uppercase;">GWTrack Communication Hub</p>';
        $html .= '<h1 style="margin:12px 0 0;font-size:24px;color:#F5FAF0;">'.e($title).'</h1>';
        $html .= '<p style="margin:8px 0 0;font-size:13px;color:#84BB41;">'.e($periodLabel).'</p>';
        $html .= '</div>';
        $html .= '<div style="padding:24px;background:#F5FAF0;">';
        $html .= '<p style="font-size:15px;line-height:1.6;">'.nl2br(e($intro)).'</p>';

        foreach ($sections as $key => $items) {
            if ($items === []) {
                continue;
            }

            $label = $sectionLabels[$key]['label'] ?? ucfirst($key);
            $html .= '<h2 style="margin:28px 0 12px;font-size:18px;color:#74A539;border-bottom:2px solid #84BB41;padding-bottom:6px;">'.e($label).'</h2>';
            $html .= '<ul style="padding-left:18px;margin:0;">';

            foreach ($items as $item) {
                $html .= '<li style="margin-bottom:16px;line-height:1.5;">';
                $html .= '<strong><a href="'.e($item['url']).'" style="color:#2D3E50;">'.e($item['title']).'</a></strong>';

                if ($item['summary']) {
                    $html .= '<br><span style="color:#475569;font-size:14px;">'.e($item['summary']).'</span>';
                }

                $html .= '</li>';
            }

            $html .= '</ul>';
        }

        $html .= '<p style="margin-top:32px;font-size:13px;color:#64748B;">Read the full feed at <a href="'.e(route('communications.index')).'" style="color:#74A539;">'.e(route('communications.index')).'</a>.</p>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * @param  array<string, list<array{title: string, summary: string|null, slug: string, published_at: string|null, url: string}>>  $sections
     */
    private function renderText(string $title, string $intro, array $sections): string
    {
        $lines = [$title, str_repeat('=', strlen($title)), '', $intro, ''];
        $sectionLabels = config('communication-hub.newsletter_sections', []);

        foreach ($sections as $key => $items) {
            if ($items === []) {
                continue;
            }

            $label = $sectionLabels[$key]['label'] ?? ucfirst($key);
            $lines[] = strtoupper($label);
            $lines[] = str_repeat('-', strlen($label));

            foreach ($items as $item) {
                $lines[] = '• '.$item['title'];

                if ($item['summary']) {
                    $lines[] = '  '.$item['summary'];
                }

                $lines[] = '  '.$item['url'];
                $lines[] = '';
            }
        }

        $lines[] = 'View all updates: '.route('communications.index');

        return implode("\n", $lines);
    }

    private function formatItem(MessageCenterAnnouncement $announcement): array
    {
        return [
            'title' => $announcement->title,
            'summary' => $announcement->summary,
            'slug' => $announcement->slug,
            'published_at' => $announcement->published_at?->toDateTimeString(),
            'url' => route('communications.show', $announcement),
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'newsletter';
        $slug = $base;
        $counter = 1;

        while (AnnouncementNewsletter::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
