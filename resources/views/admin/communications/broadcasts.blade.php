<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-4">
        <x-admin.page-hero
            eyebrow="Administration"
            title="Broadcast center"
            description="Send high-priority messages to targeted audiences."
        >
            <x-slot:actions>
                @can('view communication analytics')
                    <a href="{{ route('admin.communications.index') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                        Analytics dashboard
                    </a>
                @endcan
                <a href="{{ route('communications.index') }}" class="rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">
                    Back to hub
                </a>
            </x-slot:actions>
        </x-admin.page-hero>

        @if (session('communication_admin_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('communication_admin_status') }}
            </div>
        @endif

        <livewire:admin.communication.broadcast-center />
    </div>
</x-app-layout>
