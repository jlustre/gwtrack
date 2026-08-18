@if (count($suggestions))
    <div class="rounded-lg border border-gw-accent/40 bg-gw-accent-soft px-4 py-3 text-gw-primary">
        <strong class="text-sm">AI Completeness Hints</strong>
        <ul class="mt-2 space-y-2">
            @foreach ($suggestions as $hint)
                <li class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <span class="mr-2 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase
                        {{ $hint['priority'] === 'high' ? 'bg-red-100 text-red-800' : ($hint['priority'] === 'low' ? 'bg-slate-100 text-slate-600' : 'bg-amber-100 text-amber-800') }}">
                        {{ $hint['priority'] }}
                    </span>
                    {{ $hint['message'] }}
                </li>
            @endforeach
        </ul>
        @if ($complianceNotice)
            <p class="mt-3 text-xs leading-5 text-slate-600">{{ $complianceNotice }}</p>
        @endif
    </div>
@endif
