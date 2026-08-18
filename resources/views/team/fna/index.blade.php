<x-app-layout>
    <section class="space-y-6">
        @include('team.fna.partials.page-shell', [
            'title' => 'My FNAs',
            'description' => 'View and manage your Financial Needs Analysis records.',
            'actions' => view('team.fna.partials.dashboard-actions'),
        ])

        <livewire:fna.fna-index />
    </section>
</x-app-layout>
