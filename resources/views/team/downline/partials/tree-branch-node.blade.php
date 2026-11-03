<div x-data="genealogyTreeBranch({ childrenUrl: @js(route('team.tree.branch', $node['id'])) })" class="flex w-56 shrink-0 flex-col items-center">
    <div class="h-8 w-px shrink-0 bg-gw-accent" aria-hidden="true"></div>
    <div class="relative w-full shrink-0">
        @include('team.downline.partials.tree-node', ['node' => $node, 'isRoot' => false])

        @if ($node['has_children'])
            <button
                type="button"
                x-on:click="toggleBranch()"
                class="mt-3 w-full rounded-lg border border-gw-accent/60 bg-white/10 px-3 py-2 text-xs font-bold uppercase tracking-wide text-gw-accent hover:bg-white/15"
                :aria-expanded="expanded.toString()"
            >
                <span x-show="! expanded && ! loading">Expand branch</span>
                <span x-show="expanded && ! loading" x-cloak>Collapse branch</span>
                <span x-show="loading" x-cloak>Loading…</span>
            </button>
            <div x-show="expanded" x-cloak x-transition class="mt-2">
                <p x-show="error" x-cloak class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700" x-text="error"></p>
                <button
                    type="button"
                    x-show="error"
                    x-cloak
                    x-on:click="loadBranch()"
                    class="mt-2 rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700"
                >
                    Retry
                </button>
                <div x-show="! error" x-cloak x-ref="children" x-html="childrenHtml"></div>
            </div>
        @endif
    </div>
</div>
