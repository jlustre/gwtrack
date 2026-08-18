<?php

namespace App\Services;

use App\Models\MemberComplianceRecord;
use App\Models\User;
use App\Support\LocationOptions;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ComplianceLifecycleService
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    public function resolveMember(User $viewer, ?int $memberId = null): User
    {
        if ($memberId === null || $memberId === $viewer->id) {
            return $viewer->loadMissing(['profile']);
        }

        $member = User::query()->with('profile')->findOrFail($memberId);
        abort_unless($this->canViewMember($viewer, $member), 403);

        return $member;
    }

    public function canViewMember(User $viewer, User $member): bool
    {
        if ($viewer->id === $member->id) {
            return true;
        }

        if ($viewer->can('view licensing summary') || $viewer->can('manage licensing')) {
            return $this->hierarchy->canViewMember($viewer, $member);
        }

        return false;
    }

    public function canManageMember(User $viewer, User $member): bool
    {
        return $viewer->can('manage licensing') && $this->hierarchy->canViewMember($viewer, $member);
    }

    public function syncLicenseRecordsFromProfile(User $member): int
    {
        $keys = $member->profile?->insurance_licenses ?? [];
        $created = 0;

        foreach ($keys as $jurisdictionKey) {
            if (! is_string($jurisdictionKey) || $jurisdictionKey === '') {
                continue;
            }

            $label = LocationOptions::labelsForJurisdictionKeys([$jurisdictionKey])[0] ?? $jurisdictionKey;

            $record = MemberComplianceRecord::query()->firstOrCreate(
                [
                    'user_id' => $member->id,
                    'compliance_type' => 'state_license',
                    'jurisdiction_key' => $jurisdictionKey,
                ],
                [
                    'title' => $label.' Life License',
                    'status' => 'not_started',
                    'renewal_window_days' => config('compliance-lifecycle.types.state_license.renewal_window_days', 90),
                ],
            );

            if ($record->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @return array<string, mixed>
     */
    public function hubFor(User $viewer, User $member): array
    {
        $this->syncLicenseRecordsFromProfile($member);

        $records = MemberComplianceRecord::query()
            ->where('user_id', $member->id)
            ->orderBy('expiration_date')
            ->orderBy('title')
            ->get();

        $records->each(fn (MemberComplianceRecord $record) => $this->refreshStatus($record));

        $records = MemberComplianceRecord::query()
            ->where('user_id', $member->id)
            ->orderBy('expiration_date')
            ->orderBy('title')
            ->get();

        $stats = $this->statsFor($records);

        return [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
            ],
            'stats' => $stats,
            'records' => $records->map(fn (MemberComplianceRecord $record) => $this->serializeRecord($record))->all(),
            'groups' => $this->groupRecords($records),
            'types' => config('compliance-lifecycle.types', []),
            'is_self' => $viewer->id === $member->id,
            'can_manage' => $this->canManageMember($viewer, $member),
            'can_edit' => $viewer->id === $member->id || $this->canManageMember($viewer, $member),
            'licensing_tracker_url' => route('licensing.index'),
            'profile_licenses_url' => route('profile.edit').'#licenses',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertRecord(User $member, array $data, ?int $recordId = null): MemberComplianceRecord
    {
        $type = (string) ($data['compliance_type'] ?? '');
        abort_unless(array_key_exists($type, config('compliance-lifecycle.types', [])), 422);

        $record = $recordId
            ? MemberComplianceRecord::query()->where('user_id', $member->id)->findOrFail($recordId)
            : new MemberComplianceRecord(['user_id' => $member->id]);

        $record->fill([
            'compliance_type' => $type,
            'title' => $data['title'],
            'jurisdiction_key' => $data['jurisdiction_key'] ?: null,
            'identifier' => $data['identifier'] ?: null,
            'effective_date' => $data['effective_date'] ?: null,
            'expiration_date' => $data['expiration_date'] ?: null,
            'renewal_window_days' => $data['renewal_window_days']
                ?? config("compliance-lifecycle.types.{$type}.renewal_window_days"),
            'credits_required' => $data['credits_required'] ?: null,
            'credits_earned' => $data['credits_earned'] ?: null,
            'carrier_name' => $data['carrier_name'] ?: null,
            'notes' => $data['notes'] ?: null,
            'status' => $data['status'] ?? $record->status ?? 'pending_verification',
        ]);

        $record->save();
        $this->refreshStatus($record);

        return $record->fresh();
    }

    public function verifyRecord(User $reviewer, MemberComplianceRecord $record): MemberComplianceRecord
    {
        abort_unless($reviewer->can('manage licensing'), 403);

        $record->update([
            'verified_at' => now(),
            'verified_by' => $reviewer->id,
        ]);

        $this->refreshStatus($record);

        return $record->fresh();
    }

    public function deleteRecord(User $member, int $recordId): void
    {
        MemberComplianceRecord::query()
            ->where('user_id', $member->id)
            ->whereKey($recordId)
            ->delete();
    }

    public function refreshStatus(MemberComplianceRecord $record): MemberComplianceRecord
    {
        if ($record->status === 'not_started' && $record->expiration_date === null && $record->effective_date === null) {
            return $record;
        }

        $status = $this->calculateStatus($record);

        if ($record->status !== $status) {
            $record->update(['status' => $status]);
        }

        return $record->fresh();
    }

    /**
     * @return Collection<int, MemberComplianceRecord>
     */
    public function recordsNeedingReminder(): Collection
    {
        $reminderDays = config('compliance-lifecycle.reminder_days', [30]);

        return MemberComplianceRecord::query()
            ->whereNotNull('expiration_date')
            ->whereNotIn('status', ['expired', 'not_started'])
            ->get()
            ->filter(function (MemberComplianceRecord $record) use ($reminderDays): bool {
                $days = $record->daysUntilExpiration();

                if ($days === null || $days < 0) {
                    return false;
                }

                if (! in_array($days, $reminderDays, true)) {
                    return false;
                }

                if ($record->last_reminder_at?->isToday()) {
                    return false;
                }

                return true;
            });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function teamAlertsFor(User $viewer, int $limit = 8): array
    {
        if (! $viewer->can('view licensing summary')) {
            return [];
        }

        $memberIds = $this->hierarchy->visibleMembersQuery($viewer)->pluck('users.id');

        return MemberComplianceRecord::query()
            ->whereIn('user_id', $memberIds)
            ->whereIn('status', ['pending_renewal', 'expired', 'pending_verification'])
            ->with('user:id,name')
            ->orderBy('expiration_date')
            ->limit($limit * 3)
            ->get()
            ->sortBy(fn (MemberComplianceRecord $record) => match ($record->status) {
                'expired' => 0,
                'pending_renewal' => 1,
                default => 2,
            })
            ->take($limit)
            ->map(fn (MemberComplianceRecord $record) => [
                'member_id' => $record->user_id,
                'member_name' => $record->user?->name ?? 'Member',
                'title' => $record->title,
                'status' => $record->status,
                'status_label' => $record->statusLabel(),
                'expiration_date' => $record->expiration_date?->format('M j, Y'),
                'url' => route('compliance.index', ['member' => $record->user_id]),
            ])
            ->all();
    }

    private function calculateStatus(MemberComplianceRecord $record): string
    {
        if ($record->expiration_date === null) {
            return $record->verified_at ? 'active' : ($record->effective_date ? 'pending_verification' : 'not_started');
        }

        $days = $record->daysUntilExpiration();

        if ($days !== null && $days < 0) {
            return 'expired';
        }

        $window = $record->renewal_window_days
            ?? config('compliance-lifecycle.types.'.$record->compliance_type.'.renewal_window_days', 30);

        if ($days !== null && $days <= $window) {
            return 'pending_renewal';
        }

        return $record->verified_at || $record->effective_date ? 'active' : 'pending_verification';
    }

    /**
     * @param  Collection<int, MemberComplianceRecord>  $records
     * @return array<string, int>
     */
    private function statsFor(Collection $records): array
    {
        return [
            'total' => $records->count(),
            'active' => $records->where('status', 'active')->count(),
            'renewal_due' => $records->where('status', 'pending_renewal')->count(),
            'expired' => $records->where('status', 'expired')->count(),
            'pending_verification' => $records->where('status', 'pending_verification')->count(),
        ];
    }

    /**
     * @param  Collection<int, MemberComplianceRecord>  $records
     * @return array<int, array<string, mixed>>
     */
    private function groupRecords(Collection $records): array
    {
        return $records
            ->groupBy('compliance_type')
            ->map(function (Collection $items, string $type) {
                $config = config('compliance-lifecycle.types.'.$type, []);

                return [
                    'type' => $type,
                    'label' => $config['label'] ?? ucfirst(str_replace('_', ' ', $type)),
                    'description' => $config['description'] ?? '',
                    'items' => $items->map(fn (MemberComplianceRecord $record) => $this->serializeRecord($record))->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRecord(MemberComplianceRecord $record): array
    {
        return [
            'id' => $record->id,
            'compliance_type' => $record->compliance_type,
            'type_label' => $record->typeLabel(),
            'title' => $record->title,
            'jurisdiction_key' => $record->jurisdiction_key,
            'identifier' => $record->identifier,
            'status' => $record->status,
            'status_label' => $record->statusLabel(),
            'effective_date' => $record->effective_date?->format('M j, Y'),
            'expiration_date' => $record->expiration_date?->format('M j, Y'),
            'days_until_expiration' => $record->daysUntilExpiration(),
            'credits_required' => $record->credits_required,
            'credits_earned' => $record->credits_earned,
            'carrier_name' => $record->carrier_name,
            'notes' => $record->notes,
            'verified_at' => $record->verified_at?->format('M j, Y'),
            'is_verified' => $record->verified_at !== null,
        ];
    }
}
