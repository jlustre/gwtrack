<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <a href="{{ route('training.assignments.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; My Assignments</a>
            <h1 class="mt-2 text-3xl font-semibold">Assign Training</h1>
            <p class="mt-2 text-sm text-slate-200">Assign academy courses to members with optional due dates and notes.</p>
        </div>

        <livewire:training.assignment-manager />
    </div>
</x-app-layout>
