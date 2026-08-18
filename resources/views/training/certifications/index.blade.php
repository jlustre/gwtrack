<x-app-layout>
    <div class="space-y-6">
        <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <a href="{{ route('training.index') }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; Training Center</a>
                    <h1 class="mt-2 text-3xl font-semibold">My Certifications</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-200">Certificates earned through academy courses and assessments.</p>
                </div>
                @if (auth()->user()->can('manage training') || \App\Models\MentorAssignment::query()->where('mentor_id', auth()->id())->where('status', 'active')->exists())
                    <a href="{{ route('training.certifications.reviews') }}" class="inline-flex rounded-md border border-gw-accent/50 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gw-accent/20">
                        Review requests
                    </a>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-3">
                @forelse ($rows as $row)
                    @php
                        $record = $row['record'];
                        $certification = $row['certification'];
                    @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 class="font-semibold text-gw-primary">{{ $certification?->name }}</h2>
                                @if ($certification?->module)
                                    <p class="mt-1 text-xs text-slate-500">{{ $certification->module->title }}</p>
                                @endif
                                <p class="mt-2 text-sm text-slate-600">{{ $certification?->description }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-start gap-2 lg:items-end">
                                <span class="rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase {{ $record->status === 'issued' ? 'bg-emerald-100 text-emerald-800' : ($record->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700') }}">
                                    {{ $row['status_label'] }}
                                </span>
                                @if ($record->certificate_number)
                                    <p class="text-xs text-slate-500">{{ $record->certificate_number }}</p>
                                @endif
                                <a href="{{ route('training.certifications.show', $record) }}" class="inline-flex rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-white">
                                    View certificate
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">Complete courses and pass assessments to earn certifications.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
