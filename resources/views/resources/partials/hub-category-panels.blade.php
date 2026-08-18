@if ($documentCategories !== [])
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-gw-primary">Top document categories</h2>
        <ul class="mt-3 space-y-2">
            @foreach ($documentCategories as $category)
                <li>
                    <a
                        href="{{ route('resources.documents', ['category' => $category['key']]) }}"
                        class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 transition hover:bg-slate-50"
                    >
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-gw-primary">{{ $category['label'] }}</span>
                            <span class="block text-xs text-slate-500 line-clamp-1">{{ $category['description'] }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold tabular-nums text-slate-700 ring-1 ring-slate-200">
                            {{ $category['count'] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif

@if ($linkCategories !== [])
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-gw-primary">Top link categories</h2>
        <ul class="mt-3 space-y-2">
            @foreach ($linkCategories as $category)
                <li>
                    <a
                        href="{{ route('resources.links', ['category' => $category['key']]) }}"
                        class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 transition hover:bg-slate-50"
                    >
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-gw-primary">{{ $category['label'] }}</span>
                            <span class="block text-xs text-slate-500 line-clamp-1">{{ $category['description'] }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-bold tabular-nums text-slate-700 ring-1 ring-slate-200">
                            {{ $category['count'] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
