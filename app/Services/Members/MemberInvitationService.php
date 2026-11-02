<?php

namespace App\Services\Members;

use App\Mail\InvitationLinkMail;
use App\Models\EmailTemplate;
use App\Models\RegistrationInvitation;
use App\Models\User;
use App\Support\EmailTemplateTokens;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MemberInvitationService
{
    public function canCreateInvitation(User $user): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['member', 'agency-owner', 'team-leader', 'certified-field-mentor']);
    }

    public function createInvitation(User $sponsor, ?string $email = null): RegistrationInvitation
    {
        abort_unless($this->canCreateInvitation($sponsor), 403, 'Only active GWTracks members can send registration invitations.');

        $normalizedEmail = filled($email) ? strtolower(trim($email)) : null;

        $this->ensureEmailCanReceiveInvitation($normalizedEmail);

        return RegistrationInvitation::create([
            'sponsor_id' => $sponsor->id,
            'code' => RegistrationInvitation::generateCode(),
            'email' => $normalizedEmail,
            'role_name' => 'member',
            'max_uses' => 1,
            'uses_count' => 0,
            'expires_at' => now()->addDays(14),
        ]);
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function emailPreview(RegistrationInvitation $invitation): array
    {
        $template = EmailTemplate::query()
            ->where('key', 'member_invitation')
            ->where('is_active', true)
            ->first();

        $tokens = EmailTemplateTokens::merge([
            'app_name' => config('app.name', 'GWTracks'),
            'sponsor_name' => $invitation->sponsor?->name ?? 'A GWTracks member',
            'registration_link' => $invitation->invitationUrl(),
            'registration_code' => $invitation->code,
            'expires_at' => $invitation->expires_at?->format('F j, Y') ?? 'the expiration date',
        ], EmailTemplateTokens::pathFromUrl($invitation->invitationUrl()));

        return [
            'subject' => $template?->renderSubject($tokens) ?? 'You are invited to join '.config('app.name', 'GWTracks'),
            'body' => $template?->renderBody($tokens) ?? $invitation->invitationUrl(),
        ];
    }

    public function sendInvitationEmail(User $sponsor, RegistrationInvitation $invitation, string $recipientEmail): void
    {
        abort_unless($invitation->sponsor_id === $sponsor->id, 403);

        $normalizedEmail = strtolower(trim($recipientEmail));

        $this->ensureEmailCanReceiveInvitation($normalizedEmail, $invitation);

        $preview = $this->emailPreview($invitation);

        if (! $this->messageContainsRegistrationLink($preview['body'], $invitation->invitationUrl())) {
            throw ValidationException::withMessages([
                'recipientEmail' => 'The invitation email template must include the registration link.',
            ]);
        }

        Mail::to($normalizedEmail)->send(new InvitationLinkMail(
            $preview['subject'],
            $preview['body'],
            $sponsor->name,
            $sponsor->email,
        ));

        $invitation->forceFill([
            'email' => $normalizedEmail,
            'last_emailed_at' => now(),
        ])->save();
    }

    public function revokeInvitation(User $sponsor, RegistrationInvitation $invitation): void
    {
        abort_unless($invitation->sponsor_id === $sponsor->id, 403);
        abort_if($invitation->accepted_at !== null, 403, 'This invitation has already been used.');

        $invitation->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    public function ensureEmailCanReceiveInvitation(?string $email, ?RegistrationInvitation $currentInvitation = null): void
    {
        if (! filled($email)) {
            return;
        }

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                $currentInvitation ? 'recipient_email' : 'email' => 'This email is already registered as a GWTracks member.',
            ]);
        }

        $activeInvitationQuery = RegistrationInvitation::activeForEmail($email);

        if ($currentInvitation) {
            $activeInvitationQuery->whereKeyNot($currentInvitation->id);
        }

        if ($activeInvitationQuery->exists()) {
            throw ValidationException::withMessages([
                $currentInvitation ? 'recipient_email' : 'email' => 'An active registration link already exists for this email recipient.',
            ]);
        }
    }

    private function messageContainsRegistrationLink(string $message, string $registrationUrl): bool
    {
        if (str_contains($message, $registrationUrl)) {
            return true;
        }

        $encodedUrl = htmlspecialchars($registrationUrl, ENT_QUOTES, 'UTF-8');

        return $encodedUrl !== $registrationUrl && str_contains($message, $encodedUrl);
    }
}
