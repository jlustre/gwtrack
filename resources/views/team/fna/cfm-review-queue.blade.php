<x-app-layout>
    <section class="space-y-6">
        @include('team.fna.partials.page-shell', [
            'title' => 'CFM Review Queue',
            'description' => 'Review trainee FNA submissions, approve, or request revisions.',
        ])

        <livewire:fna.cfm-fna-review-queue />
    </section>
</x-app-layout>
