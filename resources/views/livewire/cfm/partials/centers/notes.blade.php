@php($center = $sectionCenter)

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Private mentor notes</p>
                <h2 class="mt-1 text-xl font-semibold text-gw-primary">{{ $center['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $center['description'] }}</p>
            </div>
            <a href="{{ $center['member_profile_url'] }}" class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-gw-primary hover:border-gw-accent hover:bg-gw-accent-soft">View profile</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['label' => 'Total notes', 'value' => $center['stats']['total'] ?? 0, 'theme' => 'navy'],
            ['label' => 'Strengths', 'value' => $center['stats']['strengths'] ?? 0, 'theme' => 'emerald'],
            ['label' => 'Weaknesses', 'value' => $center['stats']['weaknesses'] ?? 0, 'theme' => 'amber'],
            ['label' => 'Recommendations', 'value' => $center['stats']['recommendations'] ?? 0, 'theme' => 'gold'],
        ] as $card)
            <x-tracker-stat-card
                :label="$card['label']"
                :value="$card['value']"
                :theme="$card['theme']"
            />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-xl border border-gw-primary/20 bg-gradient-to-br from-gw-primary to-[#102847] p-5 text-white shadow-lg xl:col-span-1">
            <h3 class="text-sm font-semibold text-gw-accent">{{ $editingNoteId ? 'Edit note' : 'Add coaching note' }}</h3>
            <form wire:submit="saveNote" class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-300">Category</label>
                    <select wire:model="noteCategory" class="mt-1 w-full rounded-md border-white/20 bg-white/10 text-sm text-white shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                        @foreach ($center['categories'] as $category)
                            <option value="{{ $category }}" class="text-gw-primary">{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300">Note</label>
                    <textarea wire:model="noteBody" rows="6" class="mt-1 w-full rounded-md border-white/20 bg-white/10 text-sm text-white placeholder:text-slate-400 shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Document strengths, gaps, action plans…"></textarea>
                    @error('noteBody') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">{{ $editingNoteId ? 'Update note' : 'Save note' }}</button>
                    @if ($editingNoteId)
                        <button type="button" wire:click="cancelNoteEdit" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Cancel</button>
                    @endif
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Coaching history</h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['all' => 'All', 'strength' => 'Strengths', 'weakness' => 'Weaknesses', 'opportunity' => 'Opportunities', 'challenge' => 'Challenges', 'recommendation' => 'Recommendations', 'general' => 'General'] as $key => $label)
                        <button
                            type="button"
                            wire:click="$set('noteCategoryFilter', @js($key))"
                            @class([
                                'rounded-full px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide',
                                'bg-gw-accent text-gw-primary' => $noteCategoryFilter === $key,
                                'bg-slate-100 text-slate-600 hover:bg-slate-200' => $noteCategoryFilter !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if (count($center['notes']) === 0)
                <p class="p-6 text-sm text-slate-500">No coaching notes yet. Add your first observation on the left.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($center['notes'] as $note)
                        <li wire:key="cfm-note-{{ $note['id'] }}" class="px-5 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide',
                                            'bg-emerald-100 text-emerald-800' => $note['category'] === 'strength',
                                            'bg-amber-100 text-amber-800' => in_array($note['category'], ['weakness', 'challenge'], true),
                                            'bg-sky-100 text-sky-800' => $note['category'] === 'opportunity',
                                            'bg-gw-accent/20 text-gw-accent-dark' => $note['category'] === 'recommendation',
                                            'bg-slate-100 text-slate-700' => $note['category'] === 'general',
                                        ])>{{ $note['category_label'] }}</span>
                                        <span class="text-xs text-slate-500">{{ $note['created_at'] }}</span>
                                    </div>
                                    <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $note['body'] }}</p>
                                    <p class="mt-2 text-xs text-slate-400">By {{ $note['author'] }}</p>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <button type="button" wire:click="editNote({{ $note['id'] }})" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-gw-primary hover:border-gw-accent">Edit</button>
                                    <button type="button" wire:click="deleteNote({{ $note['id'] }})" wire:confirm="Delete this note?" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
