<x-app-layout>
    <div class="bg-zinc-950 min-h-screen -mx-4 -my-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-amber-400">Product pipeline</p>
                    <h1 class="mt-1 text-2xl font-semibold text-zinc-100">Enhancement wishlist</h1>
                </div>
                <a href="{{ route('admin.support.index') }}" class="text-sm font-semibold text-amber-400 hover:text-amber-300">← Support queue</a>
            </div>

            @if (session('support_wishlist_status'))
                <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">{{ session('support_wishlist_status') }}</div>
            @endif

            <livewire:support.admin-wishlist-board />
        </div>
    </div>
</x-app-layout>
