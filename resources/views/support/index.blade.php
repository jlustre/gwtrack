<x-app-layout>
    <div class="space-y-6">
        @include('profile.partials.member-header', [
            'user' => $user,
            'badge' => 'Help & Support',
            'showGwDetails' => false,
        ])

        @if (session('support_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('support_status') }}
            </div>
        @endif

        <livewire:support.support-ticket-wizard />

        <livewire:support.my-support-hub />
    </div>
</x-app-layout>
