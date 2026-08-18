<x-app-layout>
    <section class="space-y-6">
        @include('team.fna.partials.page-shell', [
            'title' => 'Export — '.$fna->reference_code,
            'description' => 'Print-friendly preview and PDF download.',
            'actions' => view('team.fna.partials.record-actions', ['fna' => $fna]),
        ])

        <livewire:fna.fna-export-preview :fna="$fna" :key="'fna-export-'.$fna->id" />
    </section>
</x-app-layout>
