<form method="POST" action="{{ route('admin.management.reorder', array_merge([$resource, $recordId], $indexQueryParams, ['move' => 'up'])) }}" class="inline-flex">
    @csrf
    @method('PATCH')
    <button
        type="submit"
        title="Move up"
        aria-label="Move record up"
        class="group gw-icon-btn"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m6 14 6-6 6 6" />
        </svg>
        <span class="sr-only">Move up</span>
        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">Move up</span>
    </button>
</form>

<form method="POST" action="{{ route('admin.management.reorder', array_merge([$resource, $recordId], $indexQueryParams, ['move' => 'down'])) }}" class="inline-flex">
    @csrf
    @method('PATCH')
    <button
        type="submit"
        title="Move down"
        aria-label="Move record down"
        class="group gw-icon-btn"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m6 10 6 6 6-6" />
        </svg>
        <span class="sr-only">Move down</span>
        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">Move down</span>
    </button>
</form>
