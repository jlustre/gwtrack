<div class="space-y-6">
    @if (session('admin_training_status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            @switch(session('admin_training_status'))
                @case('course-saved') Course saved. @break
                @case('lesson-saved') Lesson saved. @break
                @case('lesson-deleted') Lesson removed. @break
            @endswitch
        </div>
    @endif

    <x-admin.page-hero
        eyebrow="Admin · Academy Builder"
        :title="$module->title"
        :description="$module->slug . ' · ' . $module->lessons->count() . ' lessons'"
        class="rounded-xl"
    >
        <x-slot:actions>
            <a href="{{ route('admin.training.courses.index') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">&larr; All courses</a>
        </x-slot:actions>
    </x-admin.page-hero>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gw-primary">Course settings</h2>
            <form wire:submit="saveCourse" class="mt-4 space-y-4">
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Category</label>
                    <select wire:model="trainingCategoryId" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Title</label>
                    <input type="text" wire:model="title" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Slug</label>
                    <input type="text" wire:model="slug" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Description</label>
                    <textarea wire:model="description" rows="4" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
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
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Duration (minutes)</label>
                        <input type="number" min="1" wire:model="durationMinutes" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Sort order</label>
                        <input type="number" min="0" wire:model="sortOrder" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gw-primary">Instructor</label>
                    <select wire:model="instructorId" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">None</option>
                        @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isPublished" class="rounded border-slate-300 text-gw-accent"> Published</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isFeatured" class="rounded border-slate-300 text-gw-accent"> Featured</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="sequentialRequired" class="rounded border-slate-300 text-gw-accent"> Sequential lessons</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="dripEnabled" class="rounded border-slate-300 text-gw-accent"> Drip schedule</label>
                </div>
                <button type="submit" class="inline-flex rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white hover:bg-gw-primary-hover">Save course</button>
            </form>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">{{ $editingLessonId ? 'Edit lesson' : 'Add lesson' }}</h2>
                <form wire:submit="saveLesson" class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Title</label>
                        <input type="text" wire:model="lessonTitle" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        @error('lessonTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-gw-primary">Type</label>
                            <select wire:model="lessonType" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                @foreach ($lessonTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gw-primary">Sort order</label>
                            <input type="number" min="0" wire:model="lessonSortOrder" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Content</label>
                        <textarea wire:model="lessonContent" rows="4" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gw-primary">Video URL</label>
                        <input type="text" wire:model="lessonVideoUrl" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="lessonRequired" class="rounded border-slate-300 text-gw-accent"> Required lesson</label>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">{{ $editingLessonId ? 'Update lesson' : 'Add lesson' }}</button>
                        @if ($editingLessonId)
                            <button type="button" wire:click="cancelLessonEdit" class="inline-flex rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gw-primary">Lessons</h2>
                <div class="mt-4 space-y-2">
                    @forelse ($module->lessons->sortBy('sort_order') as $lesson)
                        <div class="flex items-start justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                            <div>
                                <p class="font-semibold text-gw-primary">{{ $lesson->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ str($lesson->lesson_type)->title() }} · Order {{ $lesson->sort_order }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="editLesson({{ $lesson->id }})" class="text-xs font-semibold text-gw-primary underline">Edit</button>
                                <button type="button" wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="Remove this lesson?" class="text-xs font-semibold text-red-700 underline">Delete</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-600">No lessons yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
