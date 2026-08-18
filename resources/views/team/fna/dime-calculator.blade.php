<x-app-layout>
    <section class="space-y-6">
        @include('team.fna.partials.page-shell', [
            'title' => 'DIME Calculator',
            'description' => 'Debt, Income, Mortgage, and Education analysis for protection planning.',
            'actions' => view('team.fna.partials.dashboard-actions'),
        ])

        <livewire:fna.dime-calculator :fna="request('fna', $prefillFnaId ?? null)" />
    </section>
</x-app-layout>
