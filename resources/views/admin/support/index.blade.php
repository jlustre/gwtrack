<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6">
        <x-admin.page-hero
            eyebrow="Administration"
            title="Support operations"
            description="Monitor ticket queues, SLA risk, and enhancement wishlist pipeline."
        >
            <x-slot:actions>
                <a
                    href="{{ route('admin.support.wishlist') }}"
                    class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90"
                >
                    Enhancement wishlist board →
                </a>
            </x-slot:actions>
        </x-admin.page-hero>

        @if (session('support_admin_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('support_admin_status') }}
            </div>
        @endif

        <livewire:support.admin-support-dashboard />
    </div>
</x-app-layout>
