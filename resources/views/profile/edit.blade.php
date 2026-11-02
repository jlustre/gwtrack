<x-app-layout>
    <livewire:profile.profile-page
        :member-user-id="$memberUserId"
        :tab="$tab ?? 'profile'"
        :open-invitations="$openInvitations ?? false"
        :already-licensed="$alreadyLicensed ?? false"
    />
</x-app-layout>
