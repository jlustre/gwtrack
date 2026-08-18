<?php

namespace App\Services;

use App\Models\AssociateParticipationAgreement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AssociateParticipationAgreementService
{
    public function prefillFromUser(User $user): array
    {
        $user->loadMissing([
            'profile.countryRecord',
            'profile.stateProvince',
            'sponsor',
        ]);

        $profile = $user->profile;

        return [
            'effective_date' => ($user->joined_at ?? now())->toDateString(),
            'full_name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'phone' => $profile?->phone ?? '',
            'associate_id' => $profile?->gw_associate_id ?? '',
            'address' => '',
            'city' => $profile?->city ?? '',
            'state_province' => $profile?->stateProvince?->name ?? $profile?->province ?? '',
            'country' => $profile?->countryRecord?->name ?? $profile?->country ?? '',
            'sponsor_name' => $user->sponsor?->name ?? '',
        ];
    }

    public function formDataForUser(User $user): array
    {
        $prefill = $this->prefillFromUser($user);

        $agreement = AssociateParticipationAgreement::query()
            ->where('user_id', $user->id)
            ->first();

        if ($agreement?->isSubmitted()) {
            return [
                'effective_date' => $agreement->effective_date?->toDateString(),
                'full_name' => $agreement->full_name,
                'email' => $agreement->email,
                'phone' => $agreement->phone ?? '',
                'associate_id' => $agreement->associate_id ?? '',
                'address' => $agreement->address ?? '',
                'city' => $agreement->city ?? '',
                'state_province' => $agreement->state_province ?? '',
                'country' => $agreement->country ?? '',
                'sponsor_name' => $agreement->sponsor_name ?? '',
                'associate_signature' => $agreement->associate_signature ?? '',
                'associate_signed_at' => $agreement->associate_signed_at?->toDateString() ?? now()->toDateString(),
                'acknowledgment_accepted' => $agreement->acknowledgment_accepted,
            ];
        }

        $formData = array_merge($prefill, [
            'address' => $agreement?->address ?? '',
            'associate_signature' => $agreement?->associate_signature ?? $prefill['full_name'],
            'associate_signed_at' => $agreement?->associate_signed_at?->toDateString() ?? now()->toDateString(),
            'acknowledgment_accepted' => (bool) ($agreement?->acknowledgment_accepted ?? false),
        ]);

        if ($agreement && ! $agreement->isSubmitted()) {
            $formData['effective_date'] = $agreement->effective_date?->toDateString() ?? $prefill['effective_date'];
        }

        return $formData;
    }

    public function submit(User $user, array $validated): AssociateParticipationAgreement
    {
        $existing = AssociateParticipationAgreement::query()
            ->where('user_id', $user->id)
            ->first();

        if ($existing?->isSubmitted()) {
            throw ValidationException::withMessages([
                'form' => 'You have already submitted this agreement.',
            ]);
        }

        $signedAt = $validated['associate_signed_at'] ?? now()->toDateString();

        $agreement = AssociateParticipationAgreement::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'effective_date' => $validated['effective_date'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'associate_id' => $validated['associate_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state_province' => $validated['state_province'] ?? null,
                'country' => $validated['country'] ?? null,
                'sponsor_name' => $validated['sponsor_name'] ?? null,
                'acknowledgment_accepted' => true,
                'associate_signature' => $validated['associate_signature'],
                'associate_signed_at' => $signedAt,
                'status' => 'submitted',
            ],
        );

        $this->generatePdf($agreement);

        return $agreement->fresh();
    }

    public function generatePdf(AssociateParticipationAgreement $agreement): AssociateParticipationAgreement
    {
        $path = 'agreements/associate-participation-'.$agreement->user_id.'.pdf';

        if ($agreement->pdf_path && $agreement->pdf_path !== $path) {
            Storage::disk('public')->delete($agreement->pdf_path);
        }

        $pdf = Pdf::loadView('pdf.associate-participation-agreement', [
            'agreement' => $agreement,
        ])->setPaper('letter', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());

        $agreement->update(['pdf_path' => $path]);

        return $agreement->fresh();
    }
}
