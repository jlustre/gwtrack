<form method="POST" action="{{ $formAction }}">
    @csrf
    @foreach ($queryParams ?? [] as $name => $value)
        @if (filled($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <button
        type="submit"
        title="{{ $isFavorited ? 'Remove from My Favorites' : 'Add to My Favorites' }}"
        aria-label="{{ $isFavorited ? 'Remove from My Favorites' : 'Add to My Favorites' }}"
        aria-pressed="{{ $isFavorited ? 'true' : 'false' }}"
        class="group {{ $isFavorited ? 'gw-icon-btn-favorite-active' : 'gw-icon-btn-favorite' }}"
    >
        @if ($isFavorited)
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
        @else
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
        @endif
        <span class="sr-only">{{ $isFavorited ? 'Remove from My Favorites' : 'Add to My Favorites' }}</span>
        <span class="pointer-events-none absolute -top-9 right-0 z-10 whitespace-nowrap rounded-md bg-gw-primary px-2 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100">
            {{ $isFavorited ? 'Unfavorite' : 'Favorite' }}
        </span>
    </button>
</form>
