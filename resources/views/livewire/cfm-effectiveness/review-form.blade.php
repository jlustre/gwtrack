<form wire:submit="submit" class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-gw-accent-soft/60 px-6 py-7 shadow-sm">
        <div class="pointer-events-none absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(132,187,65,0.18),transparent_55%)]"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-gw-accent-dark">Trainee CFM Evaluation</p>
                <h2 class="mt-2 text-3xl font-semibold text-gw-primary">Rate {{ $review->cfm->name ?? 'your CFM' }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    {{ config("cfm-effectiveness.review_triggers.{$review->trigger_type}.label") ?? 'Mentor feedback' }}
                    — choose <strong class="text-gw-primary">1 to 5 stars</strong> for each mentoring aspect.
                    Individual answers are never shown to your mentor.
                </p>
            </div>
            <div class="min-w-[16rem] rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm">
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <span>Progress</span>
                    <span>{{ $ratedCount }}/{{ $totalCount }}</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-gw-accent transition-all" style="width: {{ $progressPercent }}%"></div>
                </div>
                <p class="mt-3 text-sm text-slate-600">
                    @if ($runningAvg !== null)
                        Running average: <span class="font-bold text-gw-primary">{{ $runningAvg }}★</span>
                    @else
                        Start rating aspects below
                    @endif
                </p>
            </div>
        </div>
    </section>

    @error('ratings')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</div>
    @enderror

    @foreach ($categories as $categoryKey => $meta)
        @php $categoryQuestions = $questionsByCategory->get($categoryKey, collect()); @endphp
        @continue($categoryQuestions->isEmpty())

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-gradient-to-r from-gw-accent-soft via-white to-white px-5 py-4 md:px-6">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gw-primary">{{ $meta['label'] }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $meta['description'] }}</p>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wide text-gw-accent-dark">{{ $categoryQuestions->count() }} items</span>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach ($categoryQuestions as $index => $question)
                    <div class="grid gap-4 px-5 py-5 md:grid-cols-[minmax(0,1fr)_minmax(16rem,1.1fr)] md:items-center md:px-6 {{ $index % 2 === 1 ? 'bg-slate-50/70' : '' }}">
                        <div>
                            <p class="text-sm font-semibold text-gw-primary">{{ $question->question }}</p>
                            <p class="mt-1 text-xs text-slate-500">Tap stars to grade · 5 is highest · 1 is lowest</p>
                        </div>
                        <x-star-rating-input
                            :name="'ratings.'.$question->id"
                            :value="$ratings[$question->id] ?? null"
                            size="lg"
                        />
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- Catch-all for any questions outside configured categories --}}
    @foreach ($questionsByCategory as $categoryKey => $categoryQuestions)
        @continue(isset($categories[$categoryKey]) || $categoryQuestions->isEmpty())
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-semibold text-gw-primary">{{ str($categoryKey)->replace('_', ' ')->title() }}</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($categoryQuestions as $question)
                    <div class="grid gap-4 px-5 py-5 md:grid-cols-[minmax(0,1fr)_minmax(16rem,1.1fr)] md:items-center">
                        <p class="text-sm font-semibold text-gw-primary">{{ $question->question }}</p>
                        <x-star-rating-input :name="'ratings.'.$question->id" :value="$ratings[$question->id] ?? null" size="lg" />
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gw-primary">Open feedback</h3>
        <p class="mt-1 text-sm text-slate-600">Optional written comments help leadership coach CFMs — still anonymous.</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                <label class="text-sm font-semibold text-gw-primary">What helped you most?</label>
                <textarea wire:model="helpedMost" rows="4" class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Share specific coaching moments that made a difference..."></textarea>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                <label class="text-sm font-semibold text-gw-primary">What could be improved?</label>
                <textarea wire:model="improvements" rows="4" class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="What would make mentorship stronger for future associates?"></textarea>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                <label class="text-sm font-semibold text-gw-primary">Additional comments</label>
                <textarea wire:model="comments" rows="4" class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                <label class="text-sm font-semibold text-gw-primary">Suggestions</label>
                <textarea wire:model="suggestions" rows="4" class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
            </div>
        </div>
    </section>

    <div class="sticky bottom-4 z-20 flex flex-col gap-3 rounded-2xl border border-gw-primary/20 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-slate-600">
            @if ($progressPercent < 100)
                <span class="font-semibold text-gw-primary">{{ $totalCount - $ratedCount }} aspect(s) still need a star rating.</span>
            @else
                <span class="font-semibold text-emerald-700">All aspects rated{{ $runningAvg !== null ? ' · avg '.$runningAvg.'★' : '' }}. Ready to submit.</span>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('cfm.effectiveness.reviews') }}" wire:navigate class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                Save for later
            </a>
            <button
                type="submit"
                class="rounded-full bg-gw-primary px-6 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-gw-primary-hover disabled:cursor-not-allowed disabled:opacity-60"
                @disabled($progressPercent < 100)
            >
                Submit anonymous evaluation
            </button>
        </div>
    </div>
</form>
