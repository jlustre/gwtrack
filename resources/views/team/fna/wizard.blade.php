<x-app-layout>
    <section class="space-y-6">
        @if (session('fna_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('fna_status') }}</div>
        @endif

        @include('team.fna.partials.page-shell', [
            'title' => 'FNA Wizard — '.$fna->reference_code,
            'description' => $fna->client_name.' · '.$fna->statusLabel(),
            'actions' => view('team.fna.partials.record-actions', ['fna' => $fna]),
        ])

        <livewire:fna.fna-wizard :fna="$fna" :key="'fna-wizard-'.$fna->id" />
        <livewire:fna.fna-submit-for-review-modal />
    </section>
</x-app-layout>
