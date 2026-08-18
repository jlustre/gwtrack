<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-4">
        <x-admin.page-hero
            eyebrow="Administration"
            title="Notification operations"
        >
            <x-slot:actions>
                <a href="{{ route('admin.notifications.delivery-logs') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                    Delivery logs
                </a>
                <a href="{{ route('admin.management.index', ['category' => 'notifications']) }}" class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">
                    Configuration tables
                </a>
            </x-slot:actions>
        </x-admin.page-hero>

        @if (session('notification_admin_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('notification_admin_status') }}</div>
        @endif

        <livewire:admin.notifications.admin-notification-dashboard />
    </div>
</x-app-layout>
