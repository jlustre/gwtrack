<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('training.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Training Center</a>
                    <h1 class="mt-2 text-3xl font-semibold">FAP & Coaching Center</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">
                        Field Apprenticeship progress, mentor coaching reviews, live sessions, and CFM sign-off.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('apprenticeship.index') }}" class="inline-flex rounded-md border border-gw-accent/50 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-accent/20">
                        FAP Checklist
                    </a>
                    @if ($hub['is_mentor'])
                        <a href="{{ route('cfm.portal') }}" class="inline-flex rounded-md border border-gw-accent/50 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-accent/20">
                            CFM Portal
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <livewire:training.coaching-center />
    </div>
</x-app-layout>
