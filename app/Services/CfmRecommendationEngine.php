<?php

namespace App\Services;

use App\Support\LocationOptions;
use Illuminate\Support\Collection;

class CfmRecommendationEngine
{
    /**
     * @param  list<array<string, mixed>>  $associates
     * @param  Collection<int, array<string, mixed>>  $cfms
     * @return array<int|string, list<array<string, mixed>>>
     */
    public function recommendForAssociates(array $associates, Collection $cfms): array
    {
        $map = [];

        foreach ($associates as $associate) {
            $map[$associate['id']] = $this->recommendForAssociate($associate, $cfms);
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $associate
     * @param  Collection<int, array<string, mixed>>  $cfms
     * @return list<array<string, mixed>>
     */
    public function recommendForAssociate(array $associate, Collection $cfms): array
    {
        $jurisdictionKey = (string) ($associate['jurisdictionKey'] ?? '');

        if ($jurisdictionKey === '') {
            return [
                [
                    'type' => 'hint',
                    'label' => 'Location required',
                    'detail' => 'Add country and province/state to this associate\'s profile before matching CFMs.',
                ],
            ];
        }

        $associateTimezone = (string) ($associate['timezone'] ?? '');

        $scored = $cfms
            ->map(fn (array $cfm) => $this->scoreCfm($associate, $cfm, $associateTimezone))
            ->sortByDesc('fitScore')
            ->values();

        $licensed = $scored->filter(fn (array $row) => $row['isLicensed']);
        $unlicensed = $scored->reject(fn (array $row) => $row['isLicensed']);

        $cards = $licensed->take(3)->map(fn (array $row) => $this->toCard($row))->values()->all();
        $hints = $this->buildHints($licensed, $unlicensed);

        return array_merge($cards, $hints);
    }

    /**
     * @param  array<string, mixed>  $associate
     * @param  array<string, mixed>  $cfm
     * @return array<string, mixed>
     */
    public function scoreCfm(array $associate, array $cfm, string $associateTimezone = ''): array
    {
        $associateTimezone = $associateTimezone !== '' ? $associateTimezone : (string) ($associate['timezone'] ?? '');

        $licensed = LocationOptions::cfmCoversJurisdiction(
            $cfm['licensedJurisdictions'] ?? [],
            $associate['country'] ?? null,
            $associate['province'] ?? null
        ) === true;

        if (! $licensed) {
            return [
                'cfmId' => $cfm['id'],
                'cfmName' => $cfm['name'],
                'isLicensed' => false,
                'fitScore' => 0,
                'statusLabel' => 'Not Recommended',
                'detail' => 'Not licensed in the associate\'s jurisdiction.',
                'factors' => [],
                'workloadKey' => $cfm['workloadKey'] ?? '',
            ];
        }

        $factors = [];
        $score = 0.0;

        $recScore = (int) ($cfm['recommendationScore'] ?? 0);
        $profilePts = ($recScore / 100) * 30;
        $score += $profilePts;
        $factors[] = ['key' => 'profile', 'label' => "mentor score {$recScore}", 'points' => $profilePts];

        $active = (int) ($cfm['activeApprentices'] ?? 0);
        $max = max(1, (int) ($cfm['maxApprentices'] ?? 6));
        $capacity = max(0, 1 - ($active / $max));
        $workloadPts = $capacity * 25;
        $score += $workloadPts;
        $factors[] = ['key' => 'workload', 'label' => "{$active}/{$max} apprentice load", 'points' => $workloadPts];

        $fapRate = (float) ($cfm['fapCompletionRate'] ?? 0);
        $fapPts = ($fapRate / 100) * 15;
        $score += $fapPts;
        if ($fapRate > 0) {
            $factors[] = ['key' => 'fap', 'label' => round($fapRate).'% FAP completion', 'points' => $fapPts];
        }

        $busyness = (int) ($cfm['calendarBusyness'] ?? 0);
        $calPts = ((100 - $busyness) / 100) * 15;
        $score += $calPts;
        $factors[] = [
            'key' => 'calendar',
            'label' => $busyness <= 50 ? 'open calendar this week' : 'limited calendar slots',
            'points' => $calPts,
        ];

        $cfmTimezone = (string) ($cfm['timezone'] ?? '');
        $tzMatch = $associateTimezone !== '' && $associateTimezone !== '—' && $cfmTimezone === $associateTimezone;
        if ($tzMatch) {
            $score += 10;
            $factors[] = ['key' => 'timezone', 'label' => 'matching timezone', 'points' => 10];
        }

        if ($cfm['inMyHierarchy'] ?? false) {
            $score += 5;
            $factors[] = ['key' => 'hierarchy', 'label' => 'same hierarchy', 'points' => 5];
        }

        $uplineCfmIds = array_map('intval', $associate['uplineCfmIds'] ?? []);
        if (in_array((int) $cfm['id'], $uplineCfmIds, true)) {
            $score += 12;
            $factors[] = ['key' => 'upline', 'label' => 'trainee upline in hierarchy', 'points' => 12];
        }

        $overdue = (int) ($cfm['overdueTasks'] ?? 0);
        if ($overdue > 0) {
            $penalty = min(20, $overdue * 5);
            $score -= $penalty;
            $factors[] = [
                'key' => 'overdue',
                'label' => "{$overdue} overdue task".($overdue > 1 ? 's' : ''),
                'points' => -$penalty,
            ];
        }

        if (($cfm['workloadKey'] ?? '') === 'unavailable') {
            $score -= 30;
            $factors[] = ['key' => 'unavailable', 'label' => 'marked unavailable', 'points' => -30];
        }

        $fitScore = (int) max(0, min(100, round($score)));
        $statusLabel = $this->statusLabel($fitScore, $cfm, $overdue);

        return [
            'cfmId' => $cfm['id'],
            'cfmName' => $cfm['name'],
            'isLicensed' => true,
            'fitScore' => $fitScore,
            'statusLabel' => $statusLabel,
            'detail' => $this->detailFromFactors($factors, $associate),
            'factors' => $factors,
            'workloadKey' => $cfm['workloadKey'] ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function toCard(array $row): array
    {
        $type = match ($row['statusLabel']) {
            'Recommended' => 'best_fit',
            'Use Caution' => 'caution',
            default => 'not_recommended',
        };

        return [
            'type' => $type,
            'label' => $row['statusLabel'],
            'cfmId' => $row['cfmId'],
            'cfmName' => $row['cfmName'],
            'fitScore' => $row['fitScore'],
            'statusLabel' => $row['statusLabel'],
            'detail' => $row['detail'],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $licensed
     * @param  Collection<int, array<string, mixed>>  $unlicensed
     * @return list<array<string, mixed>>
     */
    private function buildHints(Collection $licensed, Collection $unlicensed): array
    {
        $hints = [];

        $hasTimezoneMatch = $licensed->contains(
            fn (array $row) => collect($row['factors'] ?? [])->contains('key', 'timezone')
        );

        if ($hasTimezoneMatch) {
            $hints[] = [
                'type' => 'hint',
                'detail' => 'Timezone-aligned CFMs are available for this associate.',
            ];
        }

        $hasOpenCalendar = $licensed->contains(
            fn (array $row) => collect($row['factors'] ?? [])
                ->contains(fn (array $factor) => $factor['key'] === 'calendar' && $factor['points'] >= 10)
        );

        if ($hasOpenCalendar) {
            $hints[] = [
                'type' => 'hint',
                'detail' => 'At least one licensed CFM has open mentor sessions this week.',
            ];
        }

        if ($unlicensed->isNotEmpty()) {
            $count = $unlicensed->count();
            $hints[] = [
                'type' => 'hint',
                'detail' => "{$count} accessible CFM".($count === 1 ? ' is' : 's are').' not licensed in this jurisdiction and were excluded.',
            ];
        }

        return $hints;
    }

    /**
     * @param  list<array<string, mixed>>  $factors
     * @param  array<string, mixed>  $associate
     */
    private function detailFromFactors(array $factors, array $associate): string
    {
        $province = $associate['province'] ?? 'jurisdiction';
        $parts = ["Licensed in {$province}"];

        $positive = collect($factors)
            ->filter(fn (array $factor) => ($factor['points'] ?? 0) > 0 && $factor['key'] !== 'profile')
            ->sortByDesc('points')
            ->take(2)
            ->pluck('label')
            ->all();

        return implode(' · ', array_merge($parts, $positive));
    }

    /**
     * @param  array<string, mixed>  $cfm
     */
    private function statusLabel(int $fitScore, array $cfm, int $overdue): string
    {
        $workloadKey = (string) ($cfm['workloadKey'] ?? '');

        if (in_array($workloadKey, ['unavailable', 'overloaded'], true)) {
            return 'Not Recommended';
        }

        if ($fitScore >= 75 && $overdue <= 1 && ! in_array($workloadKey, ['busy', 'overloaded'], true)) {
            return 'Recommended';
        }

        if ($fitScore >= 50) {
            return 'Use Caution';
        }

        return 'Not Recommended';
    }
}
