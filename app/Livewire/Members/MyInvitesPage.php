<?php

namespace App\Livewire\Members;

use App\Models\RegistrationInvitation;
use App\Services\Members\MemberInvitationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MyInvitesPage extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public string $inviteEmail = '';

    public ?string $statusMessage = null;

    public ?string $latestInvitationUrl = null;

    public function mount(MemberInvitationService $invitations): void
    {
        $user = auth()->user();
        abort_unless($user && $invitations->canCreateInvitation($user), 403);

        if (! in_array($this->status, ['all', 'unused', 'signed_up', 'expired'], true)) {
            $this->status = 'all';
        }
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        if (! in_array($this->status, ['all', 'unused', 'signed_up', 'expired'], true)) {
            $this->status = 'all';
        }

        $this->resetPage();
    }

    public function createInvitation(MemberInvitationService $invitations): void
    {
        $user = auth()->user();
        abort_unless($user && $invitations->canCreateInvitation($user), 403);

        $validated = $this->validate([
            'inviteEmail' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $invitation = $invitations->createInvitation(
            $user,
            $validated['inviteEmail'] ?: null,
        );

        $this->inviteEmail = '';
        $this->latestInvitationUrl = $invitation->invitationUrl();
        $this->statusMessage = 'Invitation link created. Share it with your recruit.';
        $this->status = 'unused';
        $this->resetPage();
    }

    public function destroyInvitation(int $invitationId, MemberInvitationService $invitations): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $invitation = RegistrationInvitation::query()
            ->where('sponsor_id', $user->id)
            ->whereNull('revoked_at')
            ->findOrFail($invitationId);

        $invitations->revokeInvitation($user, $invitation);

        if ($this->latestInvitationUrl === $invitation->invitationUrl()) {
            $this->latestInvitationUrl = null;
        }

        $this->statusMessage = 'Invitation link deleted.';
        $this->resetPage();
    }

    public function render(): View
    {
        $user = auth()->user();
        $baseQuery = RegistrationInvitation::query()
            ->where('sponsor_id', $user->id)
            ->whereNull('revoked_at')
            ->with('acceptedBy:id,name,email');

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'unused' => (clone $baseQuery)->where(function (Builder $query): void {
                $this->applyStatusConstraint($query, 'unused');
            })->count(),
            'signed_up' => (clone $baseQuery)->where(function (Builder $query): void {
                $this->applyStatusConstraint($query, 'signed_up');
            })->count(),
            'expired' => (clone $baseQuery)->where(function (Builder $query): void {
                $this->applyStatusConstraint($query, 'expired');
            })->count(),
        ];

        $invitations = (clone $baseQuery)
            ->when($this->status !== 'all', function (Builder $query): void {
                $this->applyStatusConstraint($query, $this->status);
            })
            ->when(filled($this->q), function (Builder $query): void {
                $term = '%'.strtolower(trim($this->q)).'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query
                        ->whereRaw('LOWER(code) like ?', [$term])
                        ->orWhereRaw('LOWER(email) like ?', [$term])
                        ->orWhereHas('acceptedBy', function (Builder $query) use ($term): void {
                            $query
                                ->whereRaw('LOWER(name) like ?', [$term])
                                ->orWhereRaw('LOWER(email) like ?', [$term]);
                        });
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.members.my-invites-page', [
            'invitations' => $invitations,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    private function applyStatusConstraint(Builder $query, string $status): void
    {
        match ($status) {
            'unused' => $query
                ->whereNull('accepted_at')
                ->whereNull('accepted_by')
                ->whereColumn('uses_count', '<', 'max_uses')
                ->where(function (Builder $query): void {
                    $query
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                }),
            'signed_up' => $query->where(function (Builder $query): void {
                $query
                    ->whereNotNull('accepted_at')
                    ->orWhereNotNull('accepted_by')
                    ->orWhereColumn('uses_count', '>=', 'max_uses');
            }),
            'expired' => $query
                ->whereNull('accepted_at')
                ->whereNull('accepted_by')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()),
            default => null,
        };
    }
}
