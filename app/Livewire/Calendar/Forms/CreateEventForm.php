<?php

namespace App\Livewire\Calendar\Forms;

use App\Models\CalendarCategory;
use App\Support\LocationOptions;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CreateEventForm extends Form
{
    public string $title = '';

    public ?string $description = null;

    public null|int|string $calendar_event_type_id = null;

    public null|int|string $calendar_category_id = null;

    public string $starts_at = '';

    public ?string $ends_at = null;

    public string $timezone = 'PST';

    public bool $is_all_day = false;

    public bool $is_recurring = false;

    public string $recurrence_frequency = 'weekly';

    public int $recurrence_interval = 1;

    /** @var list<string> */
    public array $recurrence_weekdays = [];

    public string $recurrence_end_type = 'never';

    public int $recurrence_ends_after_occurrences = 12;

    public ?string $recurrence_ends_on = null;

    public ?string $location = null;

    public ?string $meeting_link = null;

    public string $visibility = 'private';

    public string $status = 'scheduled';

    public ?string $notes = null;

    /** @var list<int|string> */
    public array $attendee_user_ids = [];

    public ?string $external_attendee_name = null;

    public ?string $external_attendee_email = null;

    /** @var list<int> */
    public array $reminder_minutes = [15];

    public string $reminder_channel = 'in_app';

    public function resetForDate(string $date): void
    {
        $day = CarbonImmutable::parse($date);

        $this->reset();
        $this->starts_at = $day->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->ends_at = $day->setTime(10, 0)->format('Y-m-d\TH:i');
        $this->timezone = auth()->user()?->profile?->timezone ?? 'PST';
        $this->calendar_category_id = CalendarCategory::query()
            ->where('slug', 'personal')
            ->whereNull('user_id')
            ->where('is_active', true)
            ->value('id');
        $this->visibility = 'private';
        $this->status = 'scheduled';
        $this->recurrence_frequency = 'weekly';
        $this->recurrence_interval = 1;
        $this->recurrence_end_type = 'never';
        $this->recurrence_ends_after_occurrences = 12;
        $this->reminder_minutes = [15];
        $this->reminder_channel = 'in_app';
    }

    public function normalizeNullableFields(): void
    {
        foreach ([
            'description',
            'calendar_event_type_id',
            'calendar_category_id',
            'ends_at',
            'recurrence_ends_on',
            'location',
            'meeting_link',
            'notes',
            'external_attendee_name',
            'external_attendee_email',
        ] as $field) {
            if ($this->{$field} === '') {
                $this->{$field} = null;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'calendar_event_type_id' => ['nullable', 'integer', 'exists:calendar_event_types,id'],
            'calendar_category_id' => ['nullable', 'integer', 'exists:calendar_categories,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'timezone' => ['required', 'string', Rule::in(array_keys(LocationOptions::timezones()))],
            'is_all_day' => ['boolean'],
            'is_recurring' => ['boolean'],
            'recurrence_frequency' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'recurrence_interval' => ['nullable', 'integer', 'min:1', 'max:12'],
            'recurrence_weekdays' => ['nullable', 'array'],
            'recurrence_weekdays.*' => ['string', Rule::in(['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'])],
            'recurrence_end_type' => ['nullable', Rule::in(['never', 'after', 'on'])],
            'recurrence_ends_after_occurrences' => ['nullable', 'integer', 'min:1', 'max:120'],
            'recurrence_ends_on' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'visibility' => ['required', Rule::in(['private', 'shared_team', 'shared_downline', 'public_organization'])],
            'status' => ['required', Rule::in(['scheduled', 'draft'])],
            'notes' => ['nullable', 'string'],
            'attendee_user_ids' => ['nullable', 'array'],
            'attendee_user_ids.*' => ['integer', 'exists:users,id'],
            'external_attendee_name' => ['nullable', 'string', 'max:255'],
            'external_attendee_email' => ['nullable', 'email', 'max:255'],
            'reminder_minutes' => ['nullable', 'array'],
            'reminder_minutes.*' => ['integer', 'min:0', 'max:43200'],
            'reminder_channel' => ['required', Rule::in(['in_app', 'email', 'both'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedPayload(): array
    {
        $this->normalizeNullableFields();

        $validated = $this->validate();

        $validated['calendar_event_type_id'] = filled($validated['calendar_event_type_id'] ?? null)
            ? (int) $validated['calendar_event_type_id']
            : null;
        $validated['calendar_category_id'] = filled($validated['calendar_category_id'] ?? null)
            ? (int) $validated['calendar_category_id']
            : null;
        $validated['recurrence_interval'] = (int) ($validated['recurrence_interval'] ?? 1);
        $validated['recurrence_ends_after_occurrences'] = (int) ($validated['recurrence_ends_after_occurrences'] ?? 12);
        $validated['attendee_user_ids'] = collect($validated['attendee_user_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->all();
        $validated['reminder_minutes'] = collect($validated['reminder_minutes'] ?? [15])
            ->map(fn ($minutes): int => (int) $minutes)
            ->all();

        return $validated;
    }
}
