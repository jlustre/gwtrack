<section class="rounded-lg border border-[#516070] bg-white/90 p-4 shadow-sm">
    <form wire:submit="createEventType" class="mb-4 grid gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-2">
        <div>
            <label for="event-title" class="block text-xs font-semibold text-slate-600">Session title</label>
            <input id="event-title" wire:model="eventTitle" required maxlength="120" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
            @error('eventTitle') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="event-duration" class="block text-xs font-semibold text-slate-600">Duration (minutes)</label>
            <input id="event-duration" type="number" min="5" max="240" step="5" wire:model="eventDuration" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
        </div>
        <div>
            <label for="event-visibility" class="block text-xs font-semibold text-slate-600">Who can book</label>
            <select id="event-visibility" wire:model="eventVisibility" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                <option value="assigned_apprentices">Assigned apprentices</option>
                <option value="public">Anyone with the public link</option>
                <option value="private">Private invitations</option>
            </select>
        </div>
        <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
            <input type="checkbox" wire:model="eventApprovalRequired" class="rounded border-slate-300">
            Require approval before confirmation
        </label>
        <div class="sm:col-span-2">
            <label for="event-description" class="block text-xs font-semibold text-slate-600">Description (optional)</label>
            <textarea id="event-description" wire:model="eventDescription" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea>
        </div>
        <div class="sm:col-span-2"><button class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white">Create event type</button></div>
    </form>
    @if ($eventTypes->isNotEmpty())
        <form wire:submit="addBookingQuestion" class="mb-4 grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-2">
            <div>
                <label for="question-event-type" class="block text-xs font-semibold text-slate-600">Add a question to</label>
                <select id="question-event-type" wire:model="questionEventTypeId" required class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                    <option value="">Choose an event type</option>
                    @foreach ($eventTypes as $type)<option value="{{ $type->id }}">{{ $type->title }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="question-type" class="block text-xs font-semibold text-slate-600">Response type</label>
                <select id="question-type" wire:model="questionType" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                    <option value="short_answer">Short answer</option>
                    <option value="long_answer">Long answer</option>
                    <option value="multiple_choice">Multiple choice</option>
                    <option value="yes_no">Yes / No</option>
                </select>
            </div>
            <div>
                <label for="question-text" class="block text-xs font-semibold text-slate-600">Question</label>
                <input id="question-text" wire:model="questionText" required maxlength="500" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                @error('questionText') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="question-options" class="block text-xs font-semibold text-slate-600">Choices (comma-separated for multiple choice)</label>
                <input id="question-options" wire:model="questionOptions" maxlength="1000" class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm">
                @error('questionOptions') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="questionRequired" class="rounded border-slate-300"> Require an answer
            </label>
            <div><button class="rounded-md border border-gw-primary px-4 py-2 text-sm font-semibold text-gw-primary">Add question</button></div>
        </form>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-[#F8FAFC] text-left text-xs font-bold uppercase text-slate-500">
                <tr><th class="px-3 py-2">Event Type</th><th class="px-3 py-2">Duration</th><th class="px-3 py-2">Approval</th><th class="px-3 py-2">Links</th><th class="px-3 py-2">Questions</th><th class="px-3 py-2">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($eventTypes as $type)
                    <tr wire:key="event-type-{{ $type->id }}">
                        <td class="px-3 py-2">
                            <div class="font-semibold text-gw-primary">{{ $type->title }}</div>
                            <div class="text-xs text-slate-500">{{ $type->slug }}</div>
                        </td>
                        <td class="px-3 py-2 text-slate-600">{{ $type->duration_minutes }} min</td>
                        <td class="px-3 py-2 text-slate-600">{{ $type->approval_required ? 'Required' : 'Auto-confirm' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $type->links_count }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $type->questions_count }}</td>
                        <td class="px-3 py-2">
                            <button type="button" wire:click="toggleEventType({{ $type->id }})" class="font-semibold text-gw-accent-dark">{{ $type->is_active ? 'Deactivate' : 'Activate' }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No booking event types yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
