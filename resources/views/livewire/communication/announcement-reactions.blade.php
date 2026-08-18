<div>
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($reactions as $code => $meta)
            @php
                $count = $counts[$code] ?? 0;
                $active = $userReaction === $code;
            @endphp
            <button
                type="button"
                wire:click="react('{{ $code }}')"
                @class([
                    'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-semibold transition',
                    'border-gw-accent bg-gw-accent-soft text-gw-accent-dark' => $active,
                    'border-slate-200 bg-white text-slate-700 hover:border-gw-accent/50 hover:bg-gw-accent-soft' => ! $active,
                ])
            >
                <span aria-hidden="true">{{ $meta['icon'] }}</span>
                <span>{{ $meta['label'] }}</span>
                @if ($count > 0)
                    <span @class(['text-xs', 'text-gw-accent-dark' => $active, 'text-slate-500' => ! $active])>{{ $count }}</span>
                @endif
            </button>
        @endforeach
    </div>
</div>
