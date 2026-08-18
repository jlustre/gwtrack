<x-app-layout>
    <div class="mb-6">
        <a href="{{ route('goals.index') }}" class="text-sm font-semibold text-gw-accent-dark hover:underline">&larr; Back to Goals</a>
    </div>
    <livewire:goals.success-blueprint :blueprint="$blueprint" />
</x-app-layout>
