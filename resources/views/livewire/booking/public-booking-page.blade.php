<div class="space-y-5">
    @if ($confirmation)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800" role="status">{{ $confirmation }}</div>
    @endif

    @if ($eventTypes->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-600">
            This booking page is unavailable or has no active sessions.
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($eventTypes as $type)
                <button
                    type="button"
                    wire:click="$set('eventTypeId', {{ $type->id }})"
                    wire:key="public-booking-type-{{ $type->id }}"
                    @class([
                        'rounded-lg border p-4 text-left transition',
                        'border-gw-accent bg-gw-accent-soft ring-2 ring-gw-accent' => $eventTypeId === $type->id,
                        'border-slate-200 bg-white hover:border-gw-accent/70' => $eventTypeId !== $type->id,
                    ])
                >
                    <span class="flex items-start justify-between gap-3">
                        <span class="font-semibold text-gw-primary">{{ $type->title }}</span>
                        <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $type->color }}"></span>
                    </span>
                    <span class="mt-1 block text-xs text-slate-600">{{ $type->duration_minutes }} minutes · {{ $type->approval_required ? 'Mentor approval required' : 'Instant confirmation' }}</span>
                    @if ($type->description)
                        <span class="mt-2 block text-sm leading-5 text-slate-600">{{ $type->description }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        @if ($selectedType)
            <form wire:submit="submit" class="space-y-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="booking-date" class="block text-sm font-semibold text-slate-700">Choose a date</label>
                        <input id="booking-date" type="date" min="{{ now()->toDateString() }}" wire:model.live="date" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                        @error('date') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end text-xs text-slate-500">
                        Times are shown in {{ $selectedType->owner->availabilitySchedules()->where('is_active', true)->orderByDesc('is_default')->value('timezone') ?? config('app.timezone') }}.
                    </div>
                </div>

                <fieldset>
                    <legend class="text-sm font-semibold text-slate-700">Available times</legend>
                    @if ($slots === [])
                        <p class="mt-2 rounded-md bg-slate-50 p-3 text-sm text-slate-600">No times are available on this date. Try another date.</p>
                    @else
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($slots as $slot)
                                <button
                                    type="button"
                                    wire:click="selectSlot(@js($slot['starts_at']))"
                                    wire:key="booking-slot-{{ md5($slot['starts_at']) }}"
                                    @class([
                                        'rounded-md border px-3 py-2 text-sm font-semibold',
                                        'border-gw-primary bg-gw-primary text-white' => $selectedSlot === $slot['starts_at'],
                                        'border-slate-300 bg-white text-gw-primary hover:border-gw-accent' => $selectedSlot !== $slot['starts_at'],
                                    ])
                                >{{ $slot['label'] }}</button>
                            @endforeach
                        </div>
                    @endif
                    @error('slot') <p class="mt-2 text-xs text-rose-700">{{ $message }}</p> @enderror
                    @error('selectedSlot') <p class="mt-2 text-xs text-rose-700">{{ $message }}</p> @enderror
                </fieldset>

                @guest
                    @if ($selectedType->visibility === 'public')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="booking-guest-name" class="block text-sm font-semibold text-slate-700">Your name</label>
                                <input id="booking-guest-name" type="text" wire:model="guestName" autocomplete="name" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                                @error('guestName') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="booking-guest-email" class="block text-sm font-semibold text-slate-700">Email address</label>
                                <input id="booking-guest-email" type="email" wire:model="guestEmail" autocomplete="email" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                                @error('guestEmail') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @else
                        <p class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Sign in with your assigned trainee account to book this session.</p>
                    @endif
                @endguest

                @if ($questions->isNotEmpty())
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-slate-700">A few details for your mentor</h3>
                        @foreach ($questions as $question)
                            <div wire:key="booking-question-{{ $question->id }}">
                                <label for="booking-question-{{ $question->id }}" class="block text-sm font-medium text-slate-700">
                                    {{ $question->question }}@if ($question->is_required) <span aria-hidden="true">*</span>@endif
                                </label>
                                @if ($question->type === 'long_answer')
                                    <textarea id="booking-question-{{ $question->id }}" wire:model="answers.{{ $question->id }}" rows="3" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea>
                                @elseif (in_array($question->type, ['yes_no', 'multiple_choice'], true))
                                    <select id="booking-question-{{ $question->id }}" wire:model="answers.{{ $question->id }}" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                                        <option value="">Choose an answer</option>
                                        @foreach (($question->type === 'yes_no' ? ['Yes', 'No'] : ($question->options ?? [])) as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input id="booking-question-{{ $question->id }}" type="text" wire:model="answers.{{ $question->id }}" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                                @endif
                                @error('answers.'.$question->id) <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="booking-reason" class="block text-sm font-semibold text-slate-700">What would you like help with? (optional)</label>
                        <textarea id="booking-reason" wire:model="reason" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea>
                    </div>
                    <div>
                        <label for="booking-topics" class="block text-sm font-semibold text-slate-700">Topics to cover (optional)</label>
                        <textarea id="booking-topics" wire:model="topics" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea>
                    </div>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-gw-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-gw-primary-hover disabled:opacity-60">
                    <span wire:loading.remove>Request this session</span>
                    <span wire:loading>Submitting…</span>
                </button>
            </form>
        @endif
    @endif
</div>
