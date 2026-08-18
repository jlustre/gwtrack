<x-app-layout>
    <div class="bg-zinc-950 min-h-screen -mx-4 -my-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <a href="{{ route('support.index') }}" class="text-sm font-semibold text-amber-400 hover:text-amber-300">← Back to support hub</a>
            <div class="mt-4">
                <livewire:support.support-ticket-detail :ticket="$ticket" />
            </div>
        </div>
    </div>
</x-app-layout>
