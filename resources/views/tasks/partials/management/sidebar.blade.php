<aside class="w-full shrink-0 space-y-4 xl:w-[340px]">
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gw-accent">Task Detail</span>
            <button type="button" x-show="selectedTask" @click="selectedTask = null" class="rounded-md border border-slate-200 px-2 py-0.5 text-xs text-slate-500 hover:border-gw-accent hover:text-gw-primary">Clear</button>
        </div>
        <div class="max-h-[520px] overflow-y-auto p-4">
            <template x-if="selectedTask">
                <div>
                    <h3 class="mb-2 text-base font-semibold leading-snug text-gw-primary" x-text="selectedTask.title"></h3>
                    <div class="mb-4 flex flex-wrap gap-1.5">
                        <span :class="priorityClass(selectedTask.priority)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold" x-text="selectedTask.priority"></span>
                        <span :class="statusClass(selectedTask.status)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold" x-text="selectedTask.status"></span>
                        <span class="rounded-full bg-gw-accent/15 px-2 py-0.5 text-[10px] font-bold text-gw-accent-dark" x-text="selectedTask.category"></span>
                    </div>
                    <dl class="mb-4 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <dt class="mb-1 font-semibold uppercase tracking-wide text-slate-500">Assigned to</dt>
                            <dd class="text-gw-primary" x-text="selectedTask.assignee"></dd>
                        </div>
                        <div>
                            <dt class="mb-1 font-semibold uppercase tracking-wide text-slate-500">Due date</dt>
                            <dd :class="selectedTask.status === 'Overdue' ? 'font-semibold text-red-700' : 'text-gw-primary'" x-text="selectedTask.due"></dd>
                        </div>
                        <div>
                            <dt class="mb-1 font-semibold uppercase tracking-wide text-slate-500">Type</dt>
                            <dd class="text-gw-primary" x-text="selectedTask.type"></dd>
                        </div>
                        <div>
                            <dt class="mb-1 font-semibold uppercase tracking-wide text-slate-500">Related</dt>
                            <dd class="text-slate-600" x-text="selectedTask.related"></dd>
                        </div>
                    </dl>
                    <p class="mb-4 text-xs leading-relaxed text-slate-600" x-text="selectedTask.desc"></p>
                    <div class="mb-4 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-gw-accent" :style="`width:${selectedTask.progress}%`"></div>
                    </div>

                    <template x-if="selectedTask.checklistItems && selectedTask.checklistItems.length">
                        <div class="mb-4">
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Checklist</p>
                            <template x-for="item in selectedTask.checklistItems" :key="item.id">
                                <label class="flex cursor-pointer items-center gap-2 border-b border-slate-100 py-2 last:border-0">
                                    <input type="checkbox" :checked="item.done" disabled class="h-3.5 w-3.5 rounded border-slate-300 text-gw-accent" />
                                    <span class="text-xs" :class="item.done ? 'text-slate-400 line-through' : 'text-slate-600'" x-text="item.text"></span>
                                </label>
                            </template>
                        </div>
                    </template>

                    <template x-if="selectedTask.comments && selectedTask.comments.length">
                        <div class="mb-4">
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Activity</p>
                            <template x-for="comment in selectedTask.comments" :key="comment.id">
                                <div class="border-b border-slate-100 py-2.5 last:border-0">
                                    <div class="mb-1 flex items-center gap-2">
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gw-primary text-[9px] font-semibold text-gw-accent" x-text="comment.initials"></span>
                                        <span class="text-[11px] font-semibold text-gw-primary" x-text="comment.author"></span>
                                        <span class="text-[10px] text-slate-400" x-text="comment.time"></span>
                                    </div>
                                    <p class="pl-7 text-[11px] leading-relaxed text-slate-600" x-text="comment.text"></p>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="selectedTask.source === 'database'">
                        <form @submit.prevent="submitTaskComment()" class="mb-4 space-y-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                            <label class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Add activity</label>
                            <textarea
                                x-model="commentBody"
                                rows="3"
                                placeholder="Log a call, note, or follow-up update..."
                                class="block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                            ></textarea>
                            <p x-show="commentError" x-cloak class="text-xs text-red-600" x-text="commentError"></p>
                            <button
                                type="submit"
                                class="w-full rounded-md bg-gw-accent px-3 py-2 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="commentSubmitting"
                                x-text="commentSubmitting ? 'Saving...' : 'Add Activity'"
                            ></button>
                        </form>
                    </template>

                    <template x-if="selectedTask.reviewUrl">
                        <form @submit.prevent="submitConfirmationReview($event)" class="mb-4 space-y-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                            @csrf
                            @method('PATCH')
                            <label class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Confirmation review</label>
                            <textarea name="review_comments" rows="3" placeholder="Add confirmation notes..." class="block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-gw-accent focus:ring-gw-accent"></textarea>
                            <p x-show="reviewError" x-cloak class="text-xs text-red-600" x-text="reviewError"></p>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="submit" name="decision" value="rejected" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60" :disabled="reviewSubmitting">Reject</button>
                                <button type="submit" name="decision" value="confirmed" class="rounded-md bg-gw-accent px-3 py-2 text-xs font-semibold text-gw-primary hover:bg-gw-accent-hover disabled:cursor-not-allowed disabled:opacity-60" :disabled="reviewSubmitting" x-text="reviewSubmitting ? 'Saving...' : 'Confirm'"></button>
                            </div>
                        </form>
                    </template>

                    <template x-if="selectedTask.actionEvent">
                        <button
                            type="button"
                            @click="openTask(selectedTask)"
                            class="inline-flex w-full items-center justify-center rounded-md bg-gw-accent px-4 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover"
                            x-text="selectedTask.actionLabel"
                        ></button>
                    </template>
                    <template x-if="! selectedTask.actionEvent && selectedTask.actionUrl">
                        <a
                            :href="selectedTask.actionUrl"
                            class="inline-flex w-full items-center justify-center rounded-md bg-gw-accent px-4 py-2.5 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover"
                            x-text="selectedTask.actionLabel"
                        ></a>
                    </template>
                </div>
            </template>
            <div x-show="!selectedTask" class="py-10 text-center">
                <p class="text-xs text-slate-500">Select a task to view details</p>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h4 class="mb-3 text-base font-semibold text-gw-primary">Fast Actions</h4>
        <div class="space-y-2">
            @foreach ($fastActions as $action)
                <a href="{{ $action['url'] }}" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2.5 text-sm transition hover:border-gw-accent hover:bg-gw-accent/5">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-gw-primary">{{ $action['label'] }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500">{{ $action['description'] }}</span>
                    </span>
                    @if (! is_null($action['count']))
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $action['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <button type="button" @click="showFormPanel = !showFormPanel" class="mb-3 flex w-full items-center justify-between text-sm font-semibold text-gw-primary">
            <span>Quick Task</span>
            <span class="text-slate-400" x-text="showFormPanel ? '−' : '+'"></span>
        </button>
        <div x-show="showFormPanel" x-transition class="space-y-3">
            <p class="text-xs text-slate-500">Manual task creation is coming soon. Use fast actions for live workflows.</p>
            <input type="text" disabled placeholder="Task title..." class="w-full rounded-md border-slate-200 bg-slate-50 text-sm text-slate-400" />
            <button type="button" disabled class="w-full cursor-not-allowed rounded-md bg-slate-100 py-2.5 text-sm font-semibold text-slate-400">Create Task</button>
        </div>
    </div>
</aside>
