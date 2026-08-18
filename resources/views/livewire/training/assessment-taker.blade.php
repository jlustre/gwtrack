<div class="space-y-6">
    <div class="overflow-visible rounded-xl border border-gw-accent/30 bg-gradient-to-br from-gw-primary via-gw-primary-hover to-gw-primary p-6 text-white shadow-lg">
        <a href="{{ route('assessments.show', $assessment) }}" class="text-sm font-semibold text-gw-accent transition hover:text-gw-accent-hover">&larr; {{ $assessment->title }}</a>
        <h1 class="mt-2 text-2xl font-semibold lg:text-3xl">Knowledge check</h1>
        <p class="mt-2 text-sm text-slate-300">{{ $questions->count() }} questions · Pass {{ $assessment->passing_score }}%</p>
    </div>

    <form wire:submit="submit" class="space-y-4">
        @foreach ($questions as $index => $question)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Question {{ $index + 1 }}</p>
                <h2 class="mt-2 text-lg font-semibold text-gw-primary">{{ $question->question }}</h2>

                @error('responses.'.$question->id)
                    <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                @enderror

                @if ($question->type === 'short_answer')
                    <textarea
                        wire:model="responses.{{ $question->id }}.text"
                        rows="3"
                        class="mt-4 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                        placeholder="Type your answer"
                    ></textarea>
                @else
                    <div class="mt-4 space-y-2">
                        @foreach ($question->answers as $answer)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-100 px-4 py-3 transition hover:border-gw-accent/40 hover:bg-gw-accent-soft">
                                <input
                                    type="radio"
                                    wire:model="responses.{{ $question->id }}.answer_id"
                                    value="{{ $answer->id }}"
                                    class="mt-1 border-slate-300 text-gw-primary focus:ring-gw-accent"
                                >
                                <span class="text-sm text-slate-700">{{ $answer->answer }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <div class="flex justify-end">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex rounded-md bg-gw-accent px-5 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="submit">Submit assessment</span>
                <span wire:loading wire:target="submit">Grading...</span>
            </button>
        </div>
    </form>
</div>
