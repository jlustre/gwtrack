<x-app-layout>
    <section class="space-y-6">
        @include('team.fna.partials.page-shell', [
            'title' => 'Agency FNA Reports',
            'description' => 'FNA activity summaries for your agency hierarchy.',
        ])

        <livewire:fna.agency-owner-fna-reports />
    </section>
</x-app-layout>
