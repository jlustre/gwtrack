<x-app-layout>
    <section class="space-y-6">
        <x-admin.page-hero
            eyebrow="Admin Management"
            :title="'View ' . str($config['label'])->singular()"
            :description="$config['description']"
        >
            <x-slot:actions>
                <a href="{{ route('admin.management.resource.index', $resource) }}" class="inline-flex items-center justify-center rounded-md border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                    Back to List
                </a>
                @if ($canManage)
                    <a href="{{ route('admin.management.edit', [$resource, $record->id]) }}" class="inline-flex items-center justify-center rounded-md border border-gw-accent/40 bg-gw-accent px-4 py-2 text-sm font-semibold text-gw-primary transition hover:bg-gw-accent/90">
                        Edit Item
                    </a>
                @endif
            </x-slot:actions>
        </x-admin.page-hero>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid gap-5 md:grid-cols-2">
                @foreach ($config['fields'] as $field)
                    @php($value = data_get($record, $field['name']))
                    <div class="{{ in_array($field['type'], ['textarea', 'rich_text'], true) ? 'md:col-span-2' : '' }}">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $field['label'] }}</dt>
                        <dd class="mt-2 rounded-md bg-slate-50 px-3 py-2 text-sm leading-6 text-slate-800">
                            @if ($field['type'] === 'boolean')
                                <span class="rounded-full {{ (bool) $value ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-700' }} px-2 py-1 text-xs font-semibold">
                                    {{ (bool) $value ? 'Yes' : 'No' }}
                                </span>
                            @elseif ($field['type'] === 'rich_text')
                                <div class="max-w-none text-sm leading-6 text-slate-800 [&_a]:text-gw-primary [&_a]:underline [&_p+p]:mt-3 [&_ul]:list-disc [&_ul]:pl-5">
                                    {!! $value ?: '<span class="text-slate-500">N/A</span>' !!}
                                </div>
                            @elseif ($field['name'] === 'country')
                                {{ $value ?: 'Global - all countries' }}
                            @else
                                {{ $value ?: 'N/A' }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
</x-app-layout>
