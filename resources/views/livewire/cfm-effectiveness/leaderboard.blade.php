<div class="space-y-6">
    <div class="flex flex-wrap gap-2">
        @foreach ($metrics as $key => $label)
            <button wire:click="$set('metric', '{{ $key }}')" class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ $metric === $key ? 'bg-gw-accent text-gw-primary' : 'bg-white text-gw-primary ring-1 ring-slate-200 hover:bg-gw-accent-soft' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="rounded-xl border border-slate-200 bg-white/90 shadow-sm backdrop-blur-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($entries as $entry)
                <div class="flex items-center gap-4 px-6 py-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gw-primary text-sm font-bold text-gw-accent">#{{ $entry['rank'] }}</span>
                    @if ($entry['photo_url'])
                        <img src="{{ $entry['photo_url'] }}" alt="" class="h-10 w-10 rounded-full object-cover">
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gw-primary">{{ $entry['name'] }}</p>
                    </div>
                    <p class="text-lg font-bold text-gw-accent">{{ number_format($entry['score'], 1) }}</p>
                </div>
            @empty
                <p class="px-6 py-8 text-sm text-slate-600">No leaderboard data yet.</p>
            @endforelse
        </div>
    </div>
</div>
