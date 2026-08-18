<x-app-layout>
    <div class="mb-6">
        <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">&larr; Back to Goals</a>
        <h1 class="mt-3 text-2xl font-semibold text-gw-primary">Activity Scorecard</h1>
        <p class="mt-1 text-sm text-slate-600">Track daily, weekly, and monthly activity performance against your plan. Log tallies from My Daily Activities.</p>
    </div>
    <livewire:goals.activity-scorecard />
</x-app-layout>
