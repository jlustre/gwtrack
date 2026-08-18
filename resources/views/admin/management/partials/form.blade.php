@php
    $fieldValue = function (string $name) use ($record) {
        $value = old($name, $record ? data_get($record, $name) : null);

        if ($value && str_contains($name, '_at')) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i');
            } catch (\Throwable) {
                return $value;
            }
        }

        if (is_string($value) && in_array($name, ['data', 'recipients', 'notification_template', 'action_link', 'channels', 'placeholders'], true)) {
            try {
                return json_encode(json_decode($value, true, 512, JSON_THROW_ON_ERROR), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    };

    $relationshipSelectTypes = [
        'training_category',
        'training_module',
        'training_module_optional',
        'assessment',
        'question',
        'rank',
        'checklist_type',
        'calendar_category',
        'calendar_event_type',
        'booking_event_type',
        'availability_schedule',
        'notification_type',
        'notification_trigger',
        'notification_template',
        'task_category',
        'task',
    ];
@endphp

@foreach ($config['fields'] as $field)
    @if (($resource ?? null) === 'resources' && $field['name'] === 'content')
        @include('admin.management.partials.resource-document-content-field', [
            'field' => $field,
            'record' => $record ?? null,
        ])
        @php
            $urlField = collect($config['fields'])->firstWhere('name', 'url');
            $isUploadedPdfRecord = ($record ?? null)
                && \App\Models\PortalResource::isUploadedPdfAttributes(
                    $record->file_path ?? null,
                    $record->file_format ?? null,
                    $record->content ?? null,
                );
        @endphp
        @if ($urlField && ! $isUploadedPdfRecord)
            @include('admin.management.partials.resource-document-url-field', [
                'field' => $urlField,
                'record' => $record ?? null,
                'fieldValue' => $fieldValue,
            ])
        @endif
        @continue
    @endif

    @if (($resource ?? null) === 'resources' && $field['name'] === 'url')
        @continue
    @endif

    @php
        $value = $fieldValue($field['name']);
        $fieldId = trim(($fieldIdPrefix ?? '').'_'.$field['name'], '_');
        $type = $field['type'];
    @endphp

    <div>
        <label for="{{ $fieldId }}" class="block text-sm font-semibold text-gw-primary">{{ $field['label'] }}</label>
        @if (! empty($field['help']))
            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $field['help'] }}</p>
        @endif

        @if ($type === 'textarea')
            <textarea
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                rows="{{ $field['rows'] ?? ($field['name'] === 'body' ? 8 : 4) }}"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >{{ $value }}</textarea>
        @endif

        @if ($type === 'rich_text')
            <textarea
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                rows="{{ $field['rows'] ?? 14 }}"
                data-rich-text
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >{{ $value }}</textarea>
        @endif

        @if ($type === 'json')
            <textarea
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                rows="6"
                placeholder='{"label":"Open item","url":"/dashboard"}'
                class="mt-2 block w-full rounded-md border-slate-300 font-mono text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >{{ $value }}</textarea>
            <p class="mt-2 text-xs leading-5 text-slate-500">Enter valid JSON.</p>
        @endif

        @if ($type === 'boolean')
            <select
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                <option value="1" @selected((string) $value === '1' || $value === true || $value === null)>Yes</option>
                <option value="0" @selected((string) $value === '0')>No</option>
            </select>
        @endif

        @if ($type === 'select')
            <select
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                @foreach ($field['options'] as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @endif

        @if ($type === 'responsible_parties')
            <input
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                type="text"
                value="{{ $value ?: 'Self' }}"
                placeholder="Self, SP, AO, TL, CFM, TR"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
            <p class="mt-2 text-xs leading-5 text-slate-500">
                Use comma-separated codes: Self, SP, AO, TL, CFM, TR.
            </p>
        @endif

        @if ($type === 'notified_parties')
            <input
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                type="text"
                value="{{ $value }}"
                placeholder="SP, AO, TL, CFM, TR"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
            <p class="mt-2 text-xs leading-5 text-slate-500">
                Optional. These people are notified when the item is completed. Use comma-separated codes: SP, AO, TL, CFM, TR.
            </p>
        @endif

        @if ($type === 'user')
            <select
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                <option value="">No user selected</option>
                @foreach ($options['users'] as $user)
                    <option value="{{ $user->id }}" @selected((string) $value === (string) $user->id)>{{ $user->name }} - {{ $user->email }}</option>
                @endforeach
            </select>
        @endif

        @if ($type === 'team')
            <select
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                <option value="">No team selected</option>
                @foreach ($options['teams'] as $team)
                    <option value="{{ $team->id }}" @selected((string) $value === (string) $team->id)>{{ $team->name }}</option>
                @endforeach
            </select>
        @endif

        @if ($type === 'checklist_types')
            @php
                $selectedIds = collect(old($field['name'], data_get($record, $field['name']) ?? []))
                    ->map(fn ($id) => (string) $id);
            @endphp
            <select
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}[]"
                multiple
                size="7"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                @foreach ($options['checklist_types'] as $option)
                    @if (($resource ?? null) === 'checklist-types' && (string) ($record->id ?? '') === (string) $option->id)
                        @continue
                    @endif
                    <option value="{{ $option->id }}" @selected($selectedIds->contains((string) $option->id))>{{ $option->name }}</option>
                @endforeach
            </select>
        @endif

        @if (in_array($type, $relationshipSelectTypes, true))
            @php
                $optionKey = match ($type) {
                    'training_category' => 'training_categories',
                    'training_module', 'training_module_optional' => 'training_modules',
                    'assessment' => 'assessments',
                    'question' => 'questions',
                    'rank' => 'ranks',
                    'checklist_type' => 'checklist_types',
                    'calendar_category' => 'calendar_categories',
                    'calendar_event_type' => 'calendar_event_types',
                    'booking_event_type' => 'booking_event_types',
                    'availability_schedule' => 'availability_schedules',
                    'notification_type' => 'notification_types',
                    'notification_trigger' => 'notification_triggers',
                    'notification_template' => 'notification_templates',
                    'task_category' => 'task_categories',
                    'task' => 'tasks',
                };
            @endphp
            <select
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
                @if ($type === 'training_module_optional' || (($field['optional'] ?? false) && $type === 'checklist_type'))
                    <option value="">None</option>
                @endif
                @foreach ($options[$optionKey] as $option)
                    @php($label = $option->name ?? $option->title ?? $option->question ?? trim(($option->code ?? '').' '.($option->name ?? '')))
                    <option value="{{ $option->id }}" @selected((string) $value === (string) $option->id)>{{ str($label)->limit(90) }}</option>
                @endforeach
            </select>
        @endif

        @if (in_array($type, ['text', 'number', 'datetime-local', 'email', 'url', 'date'], true))
            <input
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                type="{{ $type }}"
                value="{{ $value }}"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
        @endif

        @if (! in_array($type, array_merge(['textarea', 'rich_text', 'json', 'boolean', 'select', 'responsible_parties', 'notified_parties', 'user', 'team', 'text', 'number', 'datetime-local', 'email', 'url', 'date', 'checklist_types'], $relationshipSelectTypes), true))
            <input
                id="{{ $fieldId }}"
                name="{{ $field['name'] }}"
                type="text"
                value="{{ $value }}"
                class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent"
            >
        @endif

        @error($field['name'])
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error($field['name'].'.*')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
@endforeach

@if (($resource ?? null) === 'email-templates')
    @include('admin.management.partials.email-template-token-values', [
        'config' => $config,
        'record' => $record ?? null,
    ])
@endif
