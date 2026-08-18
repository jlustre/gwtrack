<?php

namespace App\Services\Prospects;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CrmGovernanceService
{
    public function reportFor(User $user): array
    {
        $ownedProspectIds = DB::table('prospects')
            ->where('owner_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('id');

        $activeShares = DB::table('prospect_shares')
            ->where('prospect_shares.granted_by', $user->id)
            ->where('prospect_shares.status', 'active')
            ->whereNull('prospect_shares.revoked_at')
            ->whereNull('prospect_shares.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('prospect_shares.expires_at')->orWhere('prospect_shares.expires_at', '>', now());
            });

        $expiringShares = (clone $activeShares)
            ->whereNotNull('prospect_shares.expires_at')
            ->whereBetween('prospect_shares.expires_at', [now(), now()->addDays(7)]);

        $staleOpenEndedShares = (clone $activeShares)
            ->whereNull('prospect_shares.expires_at')
            ->where('prospect_shares.granted_at', '<=', now()->subDays(30));

        $externalViews = DB::table('prospect_access_logs')
            ->whereIn('prospect_access_logs.prospect_id', $ownedProspectIds)
            ->where('prospect_access_logs.action', 'view')
            ->where('prospect_access_logs.created_at', '>=', now()->subDays(14))
            ->where(function ($query) use ($user): void {
                $query->whereNull('prospect_access_logs.actor_id')->orWhere('prospect_access_logs.actor_id', '!=', $user->id);
            });

        $calendarExposure = DB::table('calendar_events')
            ->join('prospects', 'prospects.id', '=', 'calendar_events.related_prospect_id')
            ->where('prospects.owner_id', $user->id)
            ->whereNull('calendar_events.deleted_at')
            ->whereNull('prospects.deleted_at')
            ->where('calendar_events.starts_at', '>=', now())
            ->whereIn('calendar_events.visibility', ['team', 'public']);

        $metrics = [
            'owned_prospects' => $ownedProspectIds->count(),
            'active_shares' => (clone $activeShares)->count(),
            'expiring_shares' => (clone $expiringShares)->count(),
            'stale_open_shares' => (clone $staleOpenEndedShares)->count(),
            'external_views' => (clone $externalViews)->count(),
            'calendar_exposure' => (clone $calendarExposure)->count(),
        ];

        return [
            'metrics' => $metrics,
            'review_items' => $this->reviewItems($metrics),
            'expiring_shares' => (clone $expiringShares)
                ->join('prospects', 'prospects.id', '=', 'prospect_shares.prospect_id')
                ->join('users as collaborators', 'collaborators.id', '=', 'prospect_shares.shared_with')
                ->orderBy('prospect_shares.expires_at')
                ->limit(5)
                ->get([
                    'prospect_shares.id',
                    'prospect_shares.expires_at',
                    'prospects.id as prospect_id',
                    'prospects.first_name',
                    'prospects.last_name',
                    'prospects.preferred_name',
                    'collaborators.name as collaborator_name',
                ]),
            'calendar_exposures' => (clone $calendarExposure)
                ->orderBy('calendar_events.starts_at')
                ->limit(5)
                ->get([
                    'calendar_events.id',
                    'calendar_events.title',
                    'calendar_events.starts_at',
                    'calendar_events.visibility',
                    'prospects.id as prospect_id',
                    'prospects.first_name',
                    'prospects.last_name',
                    'prospects.preferred_name',
                ]),
            'recent_access' => (clone $externalViews)
                ->leftJoin('prospects', 'prospects.id', '=', 'prospect_access_logs.prospect_id')
                ->leftJoin('users as actors', 'actors.id', '=', 'prospect_access_logs.actor_id')
                ->orderByDesc('prospect_access_logs.created_at')
                ->limit(5)
                ->get([
                    'prospect_access_logs.id',
                    'prospect_access_logs.created_at',
                    'prospect_access_logs.action',
                    'prospects.id as prospect_id',
                    'prospects.first_name',
                    'prospects.last_name',
                    'prospects.preferred_name',
                    'actors.name as actor_name',
                ]),
        ];
    }

    private function reviewItems(array $metrics): array
    {
        $items = [];

        if ($metrics['expiring_shares'] > 0) {
            $items[] = [
                'label' => 'Review expiring shared access',
                'detail' => "{$metrics['expiring_shares']} active share(s) expire in the next 7 days.",
                'severity' => 'attention',
            ];
        }

        if ($metrics['stale_open_shares'] > 0) {
            $items[] = [
                'label' => 'Confirm open-ended shared access',
                'detail' => "{$metrics['stale_open_shares']} share(s) have no expiry and are older than 30 days.",
                'severity' => 'warning',
            ];
        }

        if ($metrics['external_views'] > 0) {
            $items[] = [
                'label' => 'Inspect recent external profile views',
                'detail' => "{$metrics['external_views']} shared-profile view(s) happened in the last 14 days.",
                'severity' => 'info',
            ];
        }

        if ($metrics['calendar_exposure'] > 0) {
            $items[] = [
                'label' => 'Check Calendar visibility for CRM events',
                'detail' => "{$metrics['calendar_exposure']} upcoming CRM calendar event(s) are visible to team or public audiences.",
                'severity' => 'warning',
            ];
        }

        if ($items === []) {
            $items[] = [
                'label' => 'No governance reviews due',
                'detail' => 'Shared access, access logs, and linked CRM calendar events look current.',
                'severity' => 'clear',
            ];
        }

        return $items;
    }
}
