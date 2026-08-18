<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\ProspectAccessLog;
use App\Models\ProspectShare;
use App\Models\ProspectSharePermission;
use App\Models\User;
use Illuminate\Support\Collection;

class ProspectShareService
{
    public function applyVisibilityPreset(
        Prospect $prospect,
        User $owner,
        string $preset,
        ?int $explicitUserId = null,
        ?int $permissionId = null,
        ?\DateTimeInterface $expiresAt = null,
    ): void {
        $this->revokeAllForProspect($prospect, $owner);

        $targetUserIds = $this->resolvePresetTargets($owner, $preset, $explicitUserId);

        foreach ($targetUserIds as $targetUserId) {
            $targetUser = User::query()->find($targetUserId);

            if ($targetUser) {
                $this->grantShare($prospect, $owner, $targetUser, $permissionId, $expiresAt);
            }
        }

        $prospect->update(['visibility_preset' => $preset]);
    }

    public function grantShare(
        Prospect $prospect,
        User $actor,
        User $sharedWith,
        ?int $permissionId = null,
        ?\DateTimeInterface $expiresAt = null,
    ): ProspectShare {
        $permission = $this->resolvePermission($permissionId);

        $existing = ProspectShare::query()
            ->where('prospect_id', $prospect->id)
            ->where('shared_with', $sharedWith->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->first();

        if ($existing) {
            $existing->update([
                'prospect_share_permission_id' => $permission->id,
                'permission_level' => $permission->key,
                'expires_at' => $expiresAt,
                'granted_by' => $actor->id,
                'granted_at' => now(),
            ]);

            $share = $existing->fresh(['permission', 'sharedWith']);

            $this->logAccess($prospect, $actor, 'share_grant', [
                'share_id' => $share->id,
                'subject_user_id' => $sharedWith->id,
                'permission' => $permission->key,
            ]);

            return $share;
        }

        $share = ProspectShare::create([
            'prospect_id' => $prospect->id,
            'granted_by' => $actor->id,
            'shared_with' => $sharedWith->id,
            'prospect_share_permission_id' => $permission->id,
            'permission_level' => $permission->key,
            'granted_at' => now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        $share->load(['permission', 'sharedWith']);

        $this->logAccess($prospect, $actor, 'share_grant', [
            'share_id' => $share->id,
            'subject_user_id' => $sharedWith->id,
            'permission' => $permission->key,
        ]);

        return $share;
    }

    public function revokeShare(ProspectShare $share, User $actor): void
    {
        if ($share->status === 'revoked' || $share->revoked_at) {
            return;
        }

        $share->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        $this->logAccess($share->prospect, $actor, 'share_revoke', [
            'share_id' => $share->id,
            'subject_user_id' => $share->shared_with,
        ]);
    }

    public function revokeAllForProspect(Prospect $prospect, User $actor): void
    {
        $shares = ProspectShare::query()
            ->where('prospect_id', $prospect->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->get();

        foreach ($shares as $share) {
            $this->revokeShare($share, $actor);
        }
    }

    public function logAccess(Prospect $prospect, User $actor, string $action, array $metadata = []): void
    {
        ProspectAccessLog::create([
            'prospect_id' => $prospect->id,
            'actor_id' => $actor->id,
            'subject_user_id' => $metadata['subject_user_id'] ?? null,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * @return Collection<int, int>
     */
    private function resolvePresetTargets(User $owner, string $preset, ?int $explicitUserId): Collection
    {
        return match ($preset) {
            'private' => collect(),
            'cfm' => collect($owner->mentor_id)->filter(),
            'sponsor' => collect($owner->sponsor_id)->filter(),
            'manager' => collect($this->resolveManagerId($owner))->filter(),
            'team' => $this->resolveTeamMemberIds($owner),
            'user' => collect($explicitUserId)->filter(),
            default => collect(),
        };
    }

    private function resolveManagerId(User $owner): ?int
    {
        if (! $owner->sponsor_id) {
            return null;
        }

        $sponsor = $owner->sponsor;

        if (! $sponsor) {
            return $owner->sponsor_id;
        }

        return $sponsor->sponsor_id ?: $owner->sponsor_id;
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveTeamMemberIds(User $owner): Collection
    {
        return User::query()
            ->where('sponsor_id', $owner->id)
            ->where('is_active', true)
            ->permission('view shared prospects')
            ->pluck('id');
    }

    private function resolvePermission(?int $permissionId): ProspectSharePermission
    {
        if ($permissionId) {
            return ProspectSharePermission::query()->findOrFail($permissionId);
        }

        return ProspectSharePermission::query()
            ->where('key', 'view_only')
            ->firstOrFail();
    }
}
