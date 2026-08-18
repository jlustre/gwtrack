<x-app-layout>
    <section
        class="space-y-6"
        x-data="{
            manageOpen: false,
            manageLabel: '',
            manageDescription: '',
            manageUrl: '',
            openManage(label, description, url) {
                this.manageLabel = label;
                this.manageDescription = description;
                this.manageUrl = url;
                this.manageOpen = true;
            },
            closeManage() {
                this.manageOpen = false;
                this.manageUrl = '';
            }
        }"
        x-on:keydown.escape.window="closeManage()"
    >
        <x-admin.page-hero
            eyebrow="Admin Management"
            title="Operations Table Management"
            description="Manage the key setup tables that power onboarding, licensing, training, recognition, communication, events, teams, and rank advancement."
        />

        <form method="GET" action="{{ route('admin.management.index') }}" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_auto_auto_auto]">
            <div>
                <label for="search" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                <input
                    id="search"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Search table name, description, or key"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
                >
            </div>
            <div>
                <label for="category" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                <select id="category" name="category" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                    <option value="">All categories</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-2">
                <button type="submit" class="rounded-md bg-gw-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#13345f]">Filter</button>
                <a href="{{ route('admin.management.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reset</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Table</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Active Records</th>
                            <th class="px-4 py-3">Archived</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($resources as $resource)
                            @php($manageUrl = route('admin.management.resource.index', [$resource['key'], 'embedded' => 1]))
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gw-primary">{{ $resource['label'] }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $resource['table'] }}</div>
                                </td>
                                <td class="max-w-md px-4 py-3 text-slate-600">
                                    <span class="line-clamp-2">{{ $resource['description'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                        {{ $categories[$resource['category']] ?? 'Other' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format($resource['record_count']) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format($resource['archived_count']) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        x-on:click="openManage(@js($resource['label']), @js($resource['description']), @js($manageUrl))"
                                        class="inline-flex items-center justify-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent-hover"
                                    >
                                        Manage
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No setup tables match your search or filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $resources->links() }}
            </div>
        </div>

        <div
            x-show="manageOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-2 sm:p-3"
            role="dialog"
            aria-modal="true"
            aria-labelledby="manage-table-title"
        >
            <div class="absolute inset-0" x-on:click="closeManage()"></div>
            <div class="relative flex h-[90vh] w-full max-w-6xl shrink-0 flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Manage Setup Table</p>
                        <h2 id="manage-table-title" class="mt-1 text-xl font-semibold text-gw-primary" x-text="manageLabel"></h2>
                        <p class="mt-2 line-clamp-2 max-w-3xl text-sm leading-6 text-slate-600" x-text="manageDescription"></p>
                    </div>
                    <button
                        type="button"
                        x-on:click="closeManage()"
                        class="gw-icon-btn-close"
                        aria-label="Close manage modal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
                <div class="h-[calc(90vh-7rem)] min-h-[65vh] shrink-0 overflow-hidden bg-slate-50">
                    <iframe
                        x-bind:src="manageOpen ? manageUrl : ''"
                        title="Setup table manager"
                        class="block h-full w-full border-0 bg-white"
                    ></iframe>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
