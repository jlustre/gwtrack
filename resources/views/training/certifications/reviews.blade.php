<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <a href="{{ route('training.certifications.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Certifications</a>
            <h1 class="mt-2 text-3xl font-semibold">Certification Reviews</h1>
            <p class="mt-2 text-sm text-slate-200">Approve or reject trainee certification requests.</p>
        </div>

        <livewire:training.certification-reviews />
    </div>
</x-app-layout>
