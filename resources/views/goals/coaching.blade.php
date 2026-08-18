<x-app-layout>
    <div class="mb-6">
        <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">&larr; Back to Goals</a>
        <h1 class="mt-3 text-2xl font-semibold text-gw-primary">CFM Coaching</h1>
        <p class="mt-1 text-sm text-slate-600">Trainee goals, coach notes, and AI-ready recommendations.</p>
    </div>

    @if (session('goals_status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('goals_status') }}
        </div>
    @endif

    <livewire:goals.cfm-coaching-goals />
</x-app-layout>
