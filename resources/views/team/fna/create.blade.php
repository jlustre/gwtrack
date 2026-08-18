<x-app-layout>
    <section class="space-y-6">
        @include('team.fna.partials.page-shell', [
            'title' => 'Create FNA',
            'description' => 'Start a new Financial Needs Analysis draft.',
        ])

        <form method="POST" action="{{ route('team.fna.store') }}" class="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="client_name" class="block text-sm font-semibold text-slate-700">Client Name</label>
                <input id="client_name" name="client_name" type="text" value="{{ old('client_name') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" placeholder="Prospect or client name">
            </div>
            <div>
                <label for="prospect_id" class="block text-sm font-semibold text-slate-700">Link Prospect ID (optional)</label>
                <input id="prospect_id" name="prospect_id" type="text" value="{{ old('prospect_id') }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" placeholder="ULID from prospect record">
            </div>
            <button type="submit" class="inline-flex rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                Create Draft & Open Wizard
            </button>
        </form>
    </section>
</x-app-layout>
