<x-app-layout>
    <section class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            :title="'Add ' . str($config['label'])->singular()"
            :description="$config['description']"
        >
            <x-slot:actions>
                <a href="{{ route('admin.management.resource.index', array_merge([$resource], $indexQueryParams ?? [])) }}" class="inline-flex shrink-0 items-center justify-center rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                    Back to List
                </a>
            </x-slot:actions>
        </x-admin.page-hero>

        @if ($resource === 'email-templates')
            @include('admin.management.partials.email-template-tokens')
        @endif

        @if ($resource === 'resources')
            @include('admin.management.partials.resource-document-panel')
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <form
                method="POST"
                action="{{ route('admin.management.store', array_merge([$resource], $indexQueryParams ?? [])) }}"
                enctype="multipart/form-data"
                class="space-y-5"
                @if ($resource === 'resources')
                    x-data="{ contentMode: @js(old('content_source', 'compose')) }"
                @endif
            >
                @csrf
                @foreach ($indexQueryParams ?? [] as $queryKey => $queryValue)
                    <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                @endforeach
                @include('admin.management.partials.form', ['resource' => $resource])
                @if ($resource === 'resources')
                    <label
                        x-show="contentMode === 'compose'"
                        x-cloak
                        class="flex items-start gap-3 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
                    >
                        <input
                            type="checkbox"
                            name="generate_pdf"
                            value="1"
                            class="mt-1 rounded border-slate-300 text-gw-accent focus:ring-gw-accent"
                            @checked(old('generate_pdf', true))
                        >
                        <span>
                            <span class="font-semibold text-gw-primary">Generate PDF on save</span>
                            <span class="mt-1 block text-slate-600">Create the record and convert the document content into a PDF file.</span>
                        </span>
                    </label>
                @endif
                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.management.resource.index', array_merge([$resource], $indexQueryParams ?? [])) }}" class="inline-flex justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                    <button class="inline-flex justify-center rounded-md bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-hover">Create Record</button>
                </div>
            </form>
        </div>
    </section>

    @if ($resource === 'email-templates' || $resource === 'resources')
        @include('partials.rich-text-editor-scripts')
    @endif
</x-app-layout>
