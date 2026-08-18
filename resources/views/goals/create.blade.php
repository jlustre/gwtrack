<x-app-layout>
    <div class="mb-6">
        <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">&larr; Back to Goals</a>
        <h1 class="mt-3 text-2xl font-semibold text-gw-primary">Create Goal</h1>
        <p class="mt-1 text-sm text-slate-600">Nine-step SMART goal wizard with milestones and accountability.</p>
    </div>

    <livewire:goals.goal-wizard :template="request()->integer('template') ?: null" />
</x-app-layout>
