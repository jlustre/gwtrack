@props([
    'filters',
    'resetRoute' => 'team.directs',
])

<form method="GET" action="{{ route($resetRoute) }}" class="rounded-lg border border-slate-400 bg-gradient-to-br from-white via-[#F8FAFC] to-gw-accent-soft p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gw-primary">Search &amp; Filter</h2>
            <p class="mt-1 text-xs text-slate-500">Find directs by name, rank, location, mentorship, or join date.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-gw-primary px-4 text-sm font-semibold text-white hover:bg-[#132B4B]">
                Apply Filters
            </button>
            <a href="{{ route($resetRoute) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Reset
            </a>
        </div>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <label class="xl:col-span-2">
            <span class="sr-only">Search</span>
            <input
                name="search"
                value="{{ request('search') }}"
                placeholder="Search name, email, phone..."
                class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
        </label>

        <label>
            <span class="sr-only">Rank</span>
            <select name="rank_id" class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All Ranks</option>
                @foreach ($filters['ranks'] as $rank)
                    <option value="{{ $rank->id }}" @selected((string) request('rank_id') === (string) $rank->id)>{{ $rank->code }} — {{ $rank->name }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="sr-only">Country</span>
            <select name="country" class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">All Countries</option>
                @foreach ($filters['countries'] as $country)
                    <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span class="sr-only">Status</span>
            <select name="status" class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">Any Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </label>

        <label>
            <span class="sr-only">CFM assignment</span>
            <select name="mentor" class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                <option value="">Any CFM Status</option>
                <option value="assigned" @selected(request('mentor') === 'assigned')>CFM Assigned</option>
                <option value="unassigned" @selected(request('mentor') === 'unassigned')>CFM Unassigned</option>
            </select>
        </label>
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label>
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Joined From</span>
            <input name="joined_from" type="date" value="{{ request('joined_from') }}" class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
        </label>
        <label>
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Joined To</span>
            <input name="joined_to" type="date" value="{{ request('joined_to') }}" class="h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
        </label>
    </div>
</form>
