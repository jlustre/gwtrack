@php
    use App\Support\ResourceVideoCategories;
    use App\Support\VideoEmbed;

    $embed = VideoEmbed::parse($video->resolvedVideoSource());
    $thumbnail = $embed['thumbnail_url'] ?? $video->videoThumbnailUrl();
    $categoryMeta = ResourceVideoCategories::all()[$video->category ?? 'general'] ?? ResourceVideoCategories::all()['general'];
    $isFavorite = in_array($video->id, $favoriteResourceIds ?? [], true);
@endphp

<article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:border-violet-300 hover:shadow-md">
    <button type="button" class="group relative block w-full text-left" x-on:click="openPlayer({{ $video->id }})">
        <div class="aspect-video bg-gradient-to-br from-gw-primary to-violet-900">
            @if ($thumbnail)
                <img src="{{ $thumbnail }}" alt="" class="h-full w-full object-cover opacity-90 transition group-hover:opacity-100">
            @else
                <div class="flex h-full items-center justify-center text-white/80">
                    <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"></path>
                        <rect x="2" y="6" width="14" height="12" rx="2"></rect>
                    </svg>
                </div>
            @endif
            <span class="absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 transition group-hover:opacity-100">
                <span class="rounded-full bg-white/90 px-4 py-2 text-sm font-semibold text-gw-primary">Play video</span>
            </span>
            @if ($video->is_featured)
                <span class="absolute left-3 top-3 rounded-full bg-gw-accent px-2 py-0.5 text-[10px] font-bold uppercase text-gw-primary">Featured</span>
            @endif
        </div>
    </button>

    <div class="space-y-3 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase {{ $categoryMeta['accent'] }}">
                    {{ $categoryMeta['label'] }}
                </span>
                <h3 class="mt-2 font-semibold text-gw-primary">{{ $video->title }}</h3>
            </div>
            <form method="POST" action="{{ route('resources.videos.favorite', $video) }}">
                @csrf
                @foreach (request()->only(['search', 'category']) as $key => $value)
                    @if ($value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <button type="submit" class="rounded p-1 text-slate-400 hover:text-gw-accent" title="{{ $isFavorite ? 'Remove favorite' : 'Add favorite' }}">
                    <span class="sr-only">{{ $isFavorite ? 'Remove favorite' : 'Add favorite' }}</span>
                    <svg class="h-5 w-5 {{ $isFavorite ? 'fill-gw-accent text-gw-accent' : 'fill-none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                    </svg>
                </button>
            </form>
        </div>

        @if ($video->description)
            <p class="text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags($video->description), 120) }}</p>
        @endif

        <div class="flex flex-wrap gap-2">
            <button type="button" class="rounded-lg bg-violet-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-800" x-on:click="openPlayer({{ $video->id }})">
                Watch
            </button>
            @if ($video->resolvedAccessUrl())
                <a href="{{ $video->resolvedAccessUrl() }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-violet-300">
                    Open link
                </a>
            @endif
        </div>
    </div>
</article>
