<x-app-layout>
    <livewire:profile.profile-page
        :member-user-id="$memberUserId"
        :tab="$tab ?? 'profile'"
        :open-invitations="$openInvitations ?? false"
    />
</x-app-layout>
