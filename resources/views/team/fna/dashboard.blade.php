<x-app-layout>
    <section class="space-y-6">
        @if (session('fna_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('fna_status') }}
            </div>
        @endif

        @include('team.fna.partials.page-shell', [
            'title' => 'FNA Management',
            'description' => 'Learn, prepare, complete, and track Financial Needs Analysis activities with CFM guidance.',
            'actions' => view('team.fna.partials.dashboard-actions'),
        ])

        <livewire:fna.fna-dashboard />
    </section>
</x-app-layout>
