@php($center = $sectionCenter)

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">{{ ucfirst($center['key']) }}</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
                @if ($center['started'] && $center['type_start_date'])
                    <p class="mt-2 text-xs text-slate-500">
                        Started {{ $center['type_start_date'] }}
                        @if ($center['type_completion_due_date'])
                            · Target completion {{ $center['type_completion_due_date'] }}
                        @endif
                    </p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $center['member_profile_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">
                    View profile
                </a>
            </div>
        </div>
    </div>

    @if (! $center['started'])
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <h3 class="text-lg font-semibold text-gw-primary">{{ $center['empty_title'] }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ $center['empty_description'] }}</p>
        </div>
    @else
        @include('livewire.cfm.partials.centers.partials.checklist-stats', ['center' => $center])

        @if (count($center['pending_reviews']) > 0)
            @include('livewire.cfm.partials.centers.partials.pending-reviews', ['center' => $center])
        @endif

        @include('livewire.cfm.partials.centers.partials.checklist-items', ['center' => $center])
    @endif
</div>
