<div x-show="selectedTask && mobileDetail" x-cloak class="fixed inset-0 z-40 bg-slate-900/50 xl:hidden" @click="mobileDetail = false">
    <div @click.stop class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-2xl border-t border-slate-200 bg-white p-4 shadow-xl">
        <div class="mb-3 flex justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-gw-accent">Task Detail</span>
            <button type="button" @click="mobileDetail = false" class="text-sm font-medium text-slate-500 hover:text-gw-primary">Close</button>
        </div>
        <template x-if="selectedTask">
            <div>
                <h3 class="text-base font-semibold text-gw-primary" x-text="selectedTask.title"></h3>
                <p class="mt-2 text-xs text-slate-600" x-text="selectedTask.desc"></p>
                <template x-if="selectedTask.actionEvent">
                    <button type="button" @click="openTask(selectedTask)" class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-gw-accent px-4 py-2.5 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover" x-text="selectedTask.actionLabel"></button>
                </template>
                <template x-if="! selectedTask.actionEvent && selectedTask.actionUrl">
                    <a :href="selectedTask.actionUrl" class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-gw-accent px-4 py-2.5 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover" x-text="selectedTask.actionLabel"></a>
                </template>
            </div>
        </template>
    </div>
</div>
