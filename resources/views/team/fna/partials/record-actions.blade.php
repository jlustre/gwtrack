<a href="{{ route('team.fna.wizard', $fna) }}" class="inline-flex items-center justify-center rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Open Wizard</a>
@can('export', $fna)
    <a href="{{ route('team.fna.export', $fna) }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Export</a>
@endcan
<a href="{{ route('team.fna.index') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Back to List</a>
