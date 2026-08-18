<section class="grid gap-4 lg:grid-cols-3">
    @foreach (['Reminder Rules', 'Cancellation Policy', 'Calendar Conflict Checks'] as $title)
        <div class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
            <h2 class="font-semibold text-gw-primary">{{ $title }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Settings scaffold ready for Livewire forms and database-backed team defaults.</p>
        </div>
    @endforeach
</section>
