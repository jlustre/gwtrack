<div class="flex flex-col items-center">
    @forelse ($children as $child)
        @include('team.downline.partials.tree-branch-node', ['node' => $child])
    @empty
        <p class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-center text-xs text-slate-600">No visible direct recruits.</p>
    @endforelse
</div>
