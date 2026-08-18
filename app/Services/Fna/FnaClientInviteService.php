<?php

namespace App\Services\Fna;

use App\Mail\FnaClientPortalInviteMail;
use App\Models\FnaClientInvite;
use App\Models\FnaRecord;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use App\Support\FnaClientPortalHasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class FnaClientInviteService
{
    public function __construct(
        private FnaRecordService $records,
        private NotificationOrchestrator $notifications,
    ) {}

    public function agentCanSendInvites(User $user): bool
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        return $user->can('manage fna records')
            && filled($user->profile?->license_number);
    }

    public function createInvite(User $agent, ?Prospect $prospect, array $data, ?User $recipientMember = null): array
    {
        if (! $this->agentCanSendInvites($agent)) {
            throw ValidationException::withMessages([
                'agent' => 'You must have a license number on your profile to send FNA client portal invites.',
            ]);
        }

        app(FnaClientInviteRecipientService::class)->assertCanInviteRecipient($agent, $prospect, $recipientMember);

        if ($recipientMember !== null) {
            $recipientMember->loadMissing('profile');
        }

        $recipientName = trim((string) (
            $data['recipient_name']
            ?? $recipientMember?->name
            ?? $prospect?->displayName()
            ?? ''
        ));
        abort_if($recipientName === '', 422, 'Recipient name is required.');

        $securityCode = $this->generateSecurityCode();

        return DB::transaction(function () use ($agent, $prospect, $recipientMember, $data, $recipientName, $securityCode): array {
            $fna = $this->records->create($agent, [
                'title' => '[Client Portal] '.$recipientName.' FNA',
                'client_name' => $recipientName,
                'client_email' => $data['recipient_email'] ?? $recipientMember?->email ?? $prospect?->email,
                'client_phone' => $data['recipient_phone'] ?? $recipientMember?->profile?->phone ?? $prospect?->phone,
                'is_client_portal' => true,
            ], $prospect);

            $invite = FnaClientInvite::create([
                'token' => FnaClientInvite::generateToken(),
                'security_code_hash' => FnaClientPortalHasher::hashSecurityCode($securityCode),
                'sender_user_id' => $agent->id,
                'prospect_id' => $prospect?->id,
                'recipient_user_id' => $recipientMember?->id,
                'fna_record_id' => $fna->id,
                'recipient_name' => $recipientName,
                'recipient_email' => $data['recipient_email'] ?? $recipientMember?->email ?? $prospect?->email,
                'recipient_phone' => $data['recipient_phone'] ?? $recipientMember?->profile?->phone ?? $prospect?->phone,
                'personal_message' => $data['personal_message'] ?? null,
                'status' => 'pending',
                'expires_at' => now()->addDays((int) config('fna.client_portal.invite_expiry_days', 30)),
            ]);

            $relationship = app(FnaClientInviteRecipientService::class)
                ->recipientRelationshipLabel($agent, $prospect, $recipientMember);

            $this->records->logActivity(
                $fna,
                $agent,
                'client_portal_invite_created',
                'Client portal invite sent to '.$recipientName.' ('.$relationship.').',
                ['invite_id' => $invite->id, 'recipient_type' => $relationship],
            );

            return [
                'invite' => $invite->fresh(['fnaRecord', 'prospect', 'recipientUser', 'sender']),
                'security_code' => $securityCode,
            ];
        });
    }

    public function sendInviteEmail(FnaClientInvite $invite, User $agent, string $securityCode): void
    {
        $email = trim((string) $invite->recipient_email);

        if ($email === '') {
            throw ValidationException::withMessages([
                'recipient_email' => 'An email address is required to send the invite.',
            ]);
        }

        Mail::to($email)->send(new FnaClientPortalInviteMail($invite, $agent, $securityCode));

        $invite->update(['last_emailed_at' => now()]);
    }

    public function findByToken(string $token): ?FnaClientInvite
    {
        return FnaClientInvite::query()
            ->where('token', $token)
            ->with(['fnaRecord', 'sender'])
            ->first();
    }

    public function revoke(FnaClientInvite $invite, User $agent): FnaClientInvite
    {
        abort_unless((int) $invite->sender_user_id === $agent->id || $agent->hasAnyRole(['super-admin', 'admin']), 403);

        $invite->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        $this->records->logActivity(
            $invite->fnaRecord,
            $agent,
            'client_portal_invite_revoked',
            'Client portal invite revoked.',
            ['invite_id' => $invite->id],
        );

        return $invite->fresh();
    }

    public function verifySecurityCode(FnaClientInvite $invite, string $code): bool
    {
        $this->assertInviteUsable($invite);

        if (! FnaClientPortalHasher::verifySecurityCode($code, $invite->security_code_hash)) {
            return false;
        }

        if ($invite->first_opened_at === null) {
            $invite->update([
                'first_opened_at' => now(),
                'status' => $invite->status === 'pending' ? 'active' : $invite->status,
            ]);
        }

        return true;
    }

    public function setupAccessCredentials(FnaClientInvite $invite, string $email, string $phone, string $ssnLastFour): FnaClientInvite
    {
        $this->assertInviteUsable($invite);

        $invite->update([
            'access_credential_hash' => FnaClientPortalHasher::hashAccessCredentials($email, $phone, $ssnLastFour),
            'recipient_email' => FnaClientPortalHasher::normalizeEmail($email),
            'recipient_phone' => $phone,
            'status' => 'active',
        ]);

        $this->records->logActivity(
            $invite->fnaRecord,
            null,
            'client_portal_access_configured',
            'Client configured return access credentials.',
            ['invite_id' => $invite->id],
        );

        return $invite->fresh();
    }

    public function verifyAccessCredentials(string $email, string $phone, string $ssnLastFour): ?FnaClientInvite
    {
        $hash = FnaClientPortalHasher::hashAccessCredentials($email, $phone, $ssnLastFour);

        return FnaClientInvite::query()
            ->where('access_credential_hash', $hash)
            ->whereNotIn('status', ['revoked', 'expired'])
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereNull('revoked_at')
            ->with(['fnaRecord'])
            ->latest('updated_at')
            ->first();
    }

    public function markSubmitted(FnaClientInvite $invite): FnaClientInvite
    {
        $this->assertInviteUsable($invite);

        return DB::transaction(function () use ($invite): FnaClientInvite {
            $invite->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $fna = $invite->fnaRecord;

            if ($fna && in_array($fna->status, ['draft', 'revision_requested'], true)) {
                app(FnaWorkflowService::class)->transition(
                    $fna,
                    $fna->owner,
                    'ready_for_review',
                    ['source' => 'client_portal'],
                    'client_portal',
                );
            }

            $this->records->logActivity(
                $fna,
                null,
                'client_portal_submitted',
                'Client submitted FNA via client portal.',
                ['invite_id' => $invite->id],
            );

            if ($invite->sender) {
                $fna = $invite->fnaRecord;
                $clientName = $invite->recipient_name ?: $fna?->client_name ?: 'Your client';

                $this->notifications->dispatch('fna_client_portal_submitted', [
                    'queue' => true,
                    'recipients' => [$invite->sender->id],
                    'module' => 'fna',
                    'priority' => 'medium',
                    'related' => $fna ? ['type' => FnaRecord::class, 'id' => $fna->id] : null,
                    'title' => 'Client FNA submitted',
                    'message' => "{$clientName} completed their FNA via the client portal.",
                    'action_link' => $fna ? [
                        'route' => 'team.fna.show',
                        'params' => ['fnaRecord' => $fna->id],
                        'label' => 'View FNA',
                    ] : null,
                    'payload' => [
                        'fna_record_id' => $fna?->id,
                        'invite_id' => $invite->id,
                    ],
                ]);
            }

            return $invite->fresh(['fnaRecord', 'sender']);
        });
    }

    protected function generateSecurityCode(): string
    {
        $length = (int) config('fna.client_portal.security_code_length', 6);

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    protected function assertInviteUsable(FnaClientInvite $invite): void
    {
        if ($invite->revoked_at !== null || in_array($invite->status, ['revoked', 'expired', 'submitted'], true)) {
            throw ValidationException::withMessages([
                'invite' => 'This invite is no longer available.',
            ]);
        }

        if ($invite->expires_at !== null && $invite->expires_at->isPast()) {
            $invite->update(['status' => 'expired']);

            throw ValidationException::withMessages([
                'invite' => 'This invite has expired.',
            ]);
        }
    }
}
