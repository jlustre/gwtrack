<x-app-layout>
    <section class="space-y-6">
        <div class="overflow-hidden rounded-lg border border-slate-400 bg-gradient-to-br from-white via-slate-50 to-gw-accent-soft shadow-sm">
            <div class="flex flex-col gap-4 bg-gw-primary px-6 py-6 text-white md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gw-accent">Prospect Management</p>
                    <h1 class="mt-2 text-2xl font-semibold">Edit Prospect</h1>
                    <p class="mt-2 text-sm text-slate-200">{{ $prospect->displayName() }}</p>
                </div>
                <a href="{{ route('team.prospects.records.show', $prospect) }}" class="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Back to Profile</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Please fix the following errors:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('team.prospects.records.update', $prospect) }}"
            class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-4 shadow-sm sm:p-5"
        >
            @csrf
            @method('PATCH')

            @include('team.partials.prospect-record-form-fields', [
                'prospect' => $prospect,
                'prospectFunnelId' => $prospectFunnelId,
                'funnelTypes' => $funnelTypes,
                'fnaStatuses' => $fnaStatuses,
                'sources' => $sources,
                'stages' => $stages,
            ])

            <div class="mt-4 flex justify-end gap-2">
                <a href="{{ route('team.prospects.records.show', $prospect) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Save Changes</button>
            </div>
        </form>
    </section>
</x-app-layout>
