<?php

namespace App\Livewire\Calendar;

use App\Livewire\Calendar\Forms\CreateEventForm;
use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Services\Calendar\CalendarMutationService;
use App\Services\Calendar\CalendarWorkspaceService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

class CalendarPage extends Component
{
    #[Url(as: 'view', except: 'month')]
    public string $viewMode = 'month';

    #[Url]
    public string $date = '';

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $visibility = '';

    /** @var list<int> */
    public array $selectedCalendarIds = [];

    public string $statusMessage = '';

    public bool $createOpen = false;

    public bool $addCategoryOpen = false;

    public ?int $viewingEventId = null;

    public CreateEventForm $eventForm;

    public string $newCategoryName = '';

    public string $newCategoryColor = '#84BB41';

    public bool $newCategoryIsPublic = false;

    public ?int $editingCategoryId = null;

    public string $editCategoryName = '';

    public string $editCategoryColor = '#84BB41';

    public bool $editCategoryIsPublic = false;

    public function mount(CalendarWorkspaceService $workspace, ?string $viewMode = null, ?string $date = null): void
    {
        abort_unless(auth()->user()?->hasAnyPermission(['view calendar', 'view shared calendar events', 'manage organization calendar']), 403);

        if (filled($viewMode) && in_array($viewMode, ['month', 'week', 'work-week', 'day', 'agenda'], true)) {
            $this->viewMode = $viewMode;
        }

        if (! in_array($this->viewMode, ['month', 'week', 'work-week', 'day', 'agenda'], true)) {
            $this->viewMode = 'month';
        }

        $this->date = filled($date)
            ? CarbonImmutable::parse($date)->toDateString()
            : (filled($this->date) ? CarbonImmutable::parse($this->date)->toDateString() : now()->toDateString());

        if (request()->has('calendars_filter')) {
            $this->selectedCalendarIds = $workspace->normalizeSelectedCalendarIds(
                auth()->user(),
                Arr::wrap(request()->input('category_ids', [])),
                persist: true,
            );
        } else {
            $this->selectedCalendarIds = $workspace->selectedCalendarIds(auth()->user());
        }

        $this->eventForm->resetForDate($this->date);

        if (session()->has('status')) {
            $this->statusMessage = (string) session('status');
        }
    }

    public function setView(string $viewMode): void
    {
        if (in_array($viewMode, ['month', 'week', 'work-week', 'day', 'agenda'], true)) {
            $this->viewMode = $viewMode;
        }
    }

    public function goToday(): void
    {
        $this->date = now()->toDateString();
    }

    public function goPrevious(CalendarWorkspaceService $workspace): void
    {
        $this->date = $workspace->build(auth()->user(), $this->workspaceOptions())['previousDate'];
    }

    public function goNext(CalendarWorkspaceService $workspace): void
    {
        $this->date = $workspace->build(auth()->user(), $this->workspaceOptions())['nextDate'];
    }

    public function goToDate(string $date, ?string $viewMode = null): void
    {
        $this->date = CarbonImmutable::parse($date)->toDateString();

        if (filled($viewMode)) {
            $this->setView($viewMode);
        }
    }

    public function applyFilters(): void
    {
        $this->q = trim($this->q);
    }

    public function updatedSelectedCalendarIds(CalendarWorkspaceService $workspace): void
    {
        $this->persistSelectedCalendars($workspace);
    }

    public function toggleCalendar(int $categoryId, CalendarWorkspaceService $workspace): void
    {
        $ids = collect($this->selectedCalendarIds)
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->values();

        $this->selectedCalendarIds = $ids->contains($categoryId)
            ? $ids->reject(fn (int $id): bool => $id === $categoryId)->values()->all()
            : $ids->push($categoryId)->unique()->values()->all();

        $this->persistSelectedCalendars($workspace);
    }

    private function persistSelectedCalendars(CalendarWorkspaceService $workspace): void
    {
        $this->selectedCalendarIds = $workspace->normalizeSelectedCalendarIds(
            auth()->user(),
            $this->selectedCalendarIds,
            persist: true,
        );
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->can('create calendar events'), 403);

        $this->eventForm->resetForDate($this->date);
        $this->resetValidation();
        $this->createOpen = true;
    }

    public function openCreateForDay(?string $date = null): void
    {
        abort_unless(auth()->user()?->can('create calendar events'), 403);

        $targetDate = filled($date) ? CarbonImmutable::parse($date)->toDateString() : $this->date;
        $this->eventForm->resetForDate($targetDate);
        $this->resetValidation();
        $this->createOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->createOpen = false;
    }

    public function openEventDetail(int $eventId): void
    {
        $event = CalendarEvent::query()->findOrFail($eventId);
        $this->authorize('view', $event);
        $this->createOpen = false;
        $this->viewingEventId = $event->id;
    }

    public function closeEventDetail(): void
    {
        $this->viewingEventId = null;
    }

    public function createEvent(CalendarMutationService $mutations): void
    {
        abort_unless(auth()->user()?->can('create calendar events'), 403);

        try {
            $validated = $this->eventForm->validatedPayload();
            $mutations->createEvent(auth()->user(), $validated);
        } catch (ValidationException $exception) {
            $this->createOpen = true;

            $prefixed = [];
            foreach ($exception->errors() as $key => $messages) {
                $prefixed[str_starts_with($key, 'eventForm.') ? $key : 'eventForm.'.$key] = $messages;
            }

            throw ValidationException::withMessages($prefixed);
        }

        $this->createOpen = false;
        $this->eventForm->resetForDate($this->date);
        $this->statusMessage = 'Calendar event created.';
    }

    public function openAddCategoryModal(): void
    {
        $this->resetValidation();
        $this->newCategoryName = '';
        $this->newCategoryColor = '#84BB41';
        $this->newCategoryIsPublic = false;
        $this->addCategoryOpen = true;
    }

    public function closeAddCategoryModal(): void
    {
        $this->addCategoryOpen = false;
    }

    public function storeCategory(CalendarMutationService $mutations, CalendarWorkspaceService $workspace): void
    {
        abort_unless(auth()->user()?->hasPermissionTo('view calendar'), 403);

        $user = auth()->user();

        $validated = $this->validate([
            'newCategoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('calendar_categories', 'name')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
            'newCategoryColor' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'newCategoryIsPublic' => ['boolean'],
        ], [], [
            'newCategoryName' => 'name',
            'newCategoryColor' => 'color',
            'newCategoryIsPublic' => 'is public',
        ]);

        $category = $mutations->storeCategory($user, [
            'name' => $validated['newCategoryName'],
            'color' => $validated['newCategoryColor'],
            'is_public' => $validated['newCategoryIsPublic'],
        ]);

        $this->selectedCalendarIds = $workspace->selectedCalendarIds($user);
        $this->addCategoryOpen = false;
        $this->newCategoryName = '';
        $this->newCategoryColor = '#84BB41';
        $this->newCategoryIsPublic = false;
        $this->statusMessage = 'Personal calendar created.';

        unset($category);
    }

    public function startEditCategory(int $categoryId, CalendarMutationService $mutations): void
    {
        $category = CalendarCategory::query()->findOrFail($categoryId);
        abort_unless($mutations->canManageCategory(auth()->user(), $category), 403);

        $this->editingCategoryId = $category->id;
        $this->editCategoryName = $category->name;
        $this->editCategoryColor = $category->color;
        $this->editCategoryIsPublic = (bool) $category->is_public;
        $this->resetValidation();
    }

    public function cancelEditCategory(): void
    {
        $this->editingCategoryId = null;
    }

    public function updateCategory(CalendarMutationService $mutations): void
    {
        $category = CalendarCategory::query()->findOrFail($this->editingCategoryId);
        abort_unless($mutations->canManageCategory(auth()->user(), $category), 403);

        $nameRule = $category->isSystem()
            ? Rule::unique('calendar_categories', 'name')->ignore($category->id)->where(fn ($query) => $query->whereNull('user_id'))
            : Rule::unique('calendar_categories', 'name')->ignore($category->id)->where(fn ($query) => $query->where('user_id', $category->user_id));

        $validated = $this->validate([
            'editCategoryName' => ['required', 'string', 'max:255', $nameRule],
            'editCategoryColor' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'editCategoryIsPublic' => ['boolean'],
        ], [], [
            'editCategoryName' => 'name',
            'editCategoryColor' => 'color',
            'editCategoryIsPublic' => 'is public',
        ]);

        $mutations->updateCategory(auth()->user(), $category, [
            'name' => $validated['editCategoryName'],
            'color' => $validated['editCategoryColor'],
            'is_public' => $validated['editCategoryIsPublic'],
        ]);

        $this->editingCategoryId = null;
        $this->statusMessage = 'Calendar updated.';
    }

    public function deleteCategory(int $categoryId, CalendarMutationService $mutations, CalendarWorkspaceService $workspace): void
    {
        $category = CalendarCategory::query()->findOrFail($categoryId);
        $mutations->destroyCategory(auth()->user(), $category);

        $this->selectedCalendarIds = $workspace->selectedCalendarIds(auth()->user());
        if ($this->editingCategoryId === $categoryId) {
            $this->editingCategoryId = null;
        }
        $this->statusMessage = 'Calendar deleted.';
    }

    public function render(CalendarWorkspaceService $workspace): View
    {
        $data = $workspace->build(auth()->user(), $this->workspaceOptions());

        $data['viewingEvent'] = $this->viewingEventId
            ? CalendarEvent::query()
                ->with([
                    'organizer:id,name,email',
                    'type',
                    'category',
                    'attendees.user:id,name,email',
                    'relatedProspect.stage',
                    'relatedProspect.funnel',
                ])
                ->find($this->viewingEventId)
            : null;

        if ($this->viewingEventId && ! $data['viewingEvent']) {
            $this->viewingEventId = null;
        }

        return view('livewire.calendar.calendar-page', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceOptions(): array
    {
        return [
            'view_mode' => $this->viewMode,
            'date' => $this->date,
            'q' => $this->q !== '' ? $this->q : null,
            'type' => $this->type !== '' ? $this->type : null,
            'status' => $this->status !== '' ? $this->status : null,
            'visibility' => $this->visibility !== '' ? $this->visibility : null,
            'selected_calendar_ids' => $this->selectedCalendarIds,
            'persist_selected_calendars' => false,
        ];
    }
}
