<div class="grid gap-6 xl:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gw-primary">New assignment</h2>

        @if (session('assignment_status') === 'assigned')
            <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">Course assigned successfully.</p>
        @elseif (session('assignment_status') === 'cancelled')
            <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">Assignment cancelled.</p>
        @endif

        <form wire:submit="assign" class="mt-5 space-y-4">
            <div>
                <label class="text-sm font-semibold text-gw-primary">Member</label>
                <select wire:model="userId" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Select member</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                @error('userId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-semibold text-gw-primary">Course</label>
                <select wire:model="moduleId" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">Select course</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module->id }}">{{ $module->title }}</option>
                    @endforeach
                </select>
                @error('moduleId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-semibold text-gw-primary">Due date</label>
                <input type="date" wire:model="dueAt" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                @error('dueAt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-semibold text-gw-primary">Notes</label>
                <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Optional instructions for the learner"></textarea>
            </div>

            <button type="submit" class="inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover">
                Assign course
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gw-primary">Recent assignments</h2>
        <div class="mt-4 space-y-3">
            @forelse ($recentAssignments as $assignment)
                <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                    <p class="font-semibold text-gw-primary">{{ $assignment->user?->name }} · {{ $assignment->module?->title }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ str($assignment->status)->replace('_', ' ')->title() }}
                        @if ($assignment->due_at) · Due {{ $assignment->due_at->format('M j, Y') }} @endif
                    </p>
                    @if (! in_array($assignment->status, ['completed', 'cancelled'], true))
                        <button type="button" wire:click="cancel({{ $assignment->id }})" class="mt-2 text-xs font-semibold text-red-700 underline">
                            Cancel assignment
                        </button>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-600">No assignments created yet.</p>
            @endforelse
        </div>
    </div>
</div>
