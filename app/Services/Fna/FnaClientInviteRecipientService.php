<?php

namespace App\Services\Fna;

use App\Models\MentorAssignment;
use App\Models\Prospect;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Illuminate\Validation\ValidationException;

class FnaClientInviteRecipientService
{
    public function __construct(
        private DownlineHierarchyService $hierarchy,
    ) {}

    public function canInviteRecipient(User $agent, ?Prospect $prospect = null, ?User $member = null): bool
    {
        if ($agent->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ($member !== null) {
            return $this->canInviteMember($agent, $member);
        }

        if ($prospect !== null) {
            if ((int) $prospect->owner_id === $agent->id) {
                return true;
            }

            return $this->hasActiveProspectShare($agent, $prospect);
        }

        return false;
    }

    public function assertCanInviteRecipient(User $agent, ?Prospect $prospect = null, ?User $member = null): void
    {
        if ($prospect !== null && $member !== null) {
            throw ValidationException::withMessages([
                'recipient' => 'Choose either a prospect or a member recipient, not both.',
            ]);
        }

        if ($prospect === null && $member === null) {
            throw ValidationException::withMessages([
                'recipient' => 'A prospect or member recipient is required.',
            ]);
        }

        if ($this->canInviteRecipient($agent, $prospect, $member)) {
            return;
        }

        if ($member !== null) {
            throw ValidationException::withMessages([
                'recipient' => 'You can only send FNA portal invites to your prospects, downline, trainees, or members you mentor as CFM.',
            ]);
        }

        abort(403);
    }

    public function recipientRelationshipLabel(User $agent, ?Prospect $prospect, ?User $member): string
    {
        if ($member === null) {
            return 'prospect';
        }

        if ((int) $member->id === (int) $agent->id) {
            return 'self';
        }

        if ($this->hasActiveMentorAssignment($agent, $member)) {
            return 'trainee';
        }

        if ($member->sponsor_id === $agent->id) {
            return 'direct_recruit';
        }

        if ($this->hierarchy->canViewMember($agent, $member)) {
            return 'downline';
        }

        return 'member';
    }

    public function prefillFromMember(User $member): array
    {
        $member->loadMissing('profile');

        return [
            'recipient_name' => $member->name,
            'recipient_email' => $member->email,
            'recipient_phone' => (string) ($member->profile?->phone ?? ''),
        ];
    }

    protected function canInviteMember(User $agent, User $member): bool
    {
        if ((int) $member->id === (int) $agent->id) {
            return false;
        }

        if ($this->hasActiveMentorAssignment($agent, $member)) {
            return true;
        }

        return $this->hierarchy->canViewMember($agent, $member);
    }

    protected function hasActiveMentorAssignment(User $agent, User $member): bool
    {
        return MentorAssignment::query()
            ->where('mentor_id', $agent->id)
            ->where('apprentice_id', $member->id)
            ->where('status', 'active')
            ->exists();
    }

    protected function hasActiveProspectShare(User $agent, Prospect $prospect): bool
    {
        return $prospect->shares()
            ->where('shared_with', $agent->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
