<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-4">
        <x-admin.page-hero
            eyebrow="Administration"
            title="Acknowledgement report"
            description="Track compliance for required announcements."
        >
            <x-slot:actions>
                <a href="{{ route('admin.communications.index') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                    Analytics dashboard
                </a>
                <a href="{{ route('communications.index') }}" class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">
                    Back to hub
                </a>
            </x-slot:actions>
        </x-admin.page-hero>

        <livewire:admin.communication.admin-announcement-acknowledgements />
    </div>
</x-app-layout>
