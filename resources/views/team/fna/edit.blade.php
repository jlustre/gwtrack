<x-app-layout>
    <section class="space-y-6">
        @include('team.fna.partials.page-shell', [
            'title' => 'Edit FNA — '.$fna->reference_code,
            'description' => 'Full edit form arrives in Phase 2 wizard.',
            'actions' => view('team.fna.partials.record-actions', ['fna' => $fna]),
        ])
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Use the wizard for structured editing. Single-page edit UI is planned for Phase 2.</p>
    </section>
</x-app-layout>
