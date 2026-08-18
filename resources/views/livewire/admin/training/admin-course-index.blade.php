<div class="space-y-6">
    <x-admin.page-hero
        eyebrow="Admin · Academy Builder"
        title="Course Builder"
        description="Create and manage academy courses."
        class="rounded-xl"
    >
        <x-slot:actions>
            <a href="{{ route('admin.training.index') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">&larr; Training Studio</a>
        </x-slot:actions>
    </x-admin.page-hero>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-gw-primary">Courses</h2>
            <button type="button" wire:click="$toggle('showCreate')" class="inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">
                {{ $showCreate ? 'Close form' : 'New course' }}
            </button>
        </div>

        @if ($showCreate)
            <form wire:submit="createCourse" class="mt-5 grid gap-4 border-t border-slate-100 pt-5 lg:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Category</label>
                    <select wire:model="trainingCategoryId" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('trainingCategoryId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Title</label>
                    <input type="text" wire:model="title" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="lg:col-span-2">
                    <label class="text-sm font-semibold text-gw-primary">Description</label>
                    <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Course type</label>
                    <select wire:model="courseType" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($courseTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Difficulty</label>
                    <select wire:model="difficulty" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($difficulties as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2 lg:col-span-2">
                    <input type="checkbox" wire:model="isPublished" id="create-published" class="rounded border-slate-300 text-gw-accent">
                    <label for="create-published" class="text-sm text-slate-700">Publish immediately</label>
                </div>
                <div class="lg:col-span-2">
                    <button type="submit" class="inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Create course</button>
                </div>
            </form>
        @endif

        <div class="mt-5 space-y-3">
            @forelse ($courses as $course)
                <a href="{{ route('admin.training.courses.show', $course) }}" class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3 transition hover:border-gw-accent/40 hover:bg-gw-accent-soft">
                    <div>
                        <p class="font-semibold text-gw-primary">{{ $course->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $course->category?->name }} · {{ $course->lessons_count }} lessons · {{ $course->slug }}</p>
                    </div>
                    <span @class([
                        'rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase',
                        'bg-emerald-100 text-emerald-800' => $course->is_published,
                        'bg-amber-100 text-amber-800' => ! $course->is_published,
                    ])>{{ $course->is_published ? 'Published' : 'Draft' }}</span>
                </a>
            @empty
                <p class="text-sm text-slate-600">No courses yet. Create your first academy course.</p>
            @endforelse
        </div>
    </div>
</div>
