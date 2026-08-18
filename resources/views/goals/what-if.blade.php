<x-app-layout>
    <div class="mb-6">
        <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">&larr; Back to Goals</a>
        <h1 class="mt-3 text-2xl font-semibold text-gw-primary">What-If Calculator</h1>
        <p class="mt-1 text-sm text-slate-600">Simulate targets and instantly see required activities at every funnel stage.</p>
    </div>
    <livewire:goals.what-if-calculator />
</x-app-layout>
