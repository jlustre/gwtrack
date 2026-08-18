<a href="{{ route('team.fna.create') }}" class="inline-flex items-center justify-center rounded-lg border border-gw-accent bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary shadow-sm transition hover:bg-gw-accent-hover">
    + New FNA
</a>
<a href="{{ route('team.fna.index') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
    My FNAs
</a>
<a href="{{ route('team.fna.dime') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
    DIME Calculator
</a>
@can('review trainee fna records')
    <a href="{{ route('team.fna.cfm.review-queue') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
        CFM Review Queue
    </a>
@endcan
@can('view fna agency reports')
    <a href="{{ route('team.fna.reports.agency') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
        Agency Reports
    </a>
@endcan
