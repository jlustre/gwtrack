<?php



namespace App\Models;



use App\Support\SystemTaskAssignor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;



class TaskUser extends Model

{

    use SoftDeletes;



    protected $fillable = [

        'assignee_id',

        'assignor_id',

        'task_id',

        'task_category_id',

        'additional_notes',

        'priority',

        'status',

        'related_module',

        'related_person',

        'related_prospect_id',

        'related_fna_id',

        'due_date',

        'progress',

        'reminder',

        'completed_at',

        'delegation_tracker_archived_at',

    ];



    protected function casts(): array

    {

        return [

            'due_date' => 'date',

            'completed_at' => 'datetime',

            'delegation_tracker_archived_at' => 'datetime',

            'progress' => 'integer',

        ];

    }



    public function assignee(): BelongsTo

    {

        return $this->belongsTo(User::class, 'assignee_id');

    }



    public function assignor(): BelongsTo

    {

        return $this->belongsTo(User::class, 'assignor_id');

    }



    public function creator(): BelongsTo

    {

        return $this->assignor();

    }



    public function task(): BelongsTo

    {

        return $this->belongsTo(Task::class);

    }



    public function taskCategory(): BelongsTo

    {

        return $this->belongsTo(TaskCategory::class, 'task_category_id');

    }



    public function relatedProspect(): BelongsTo

    {

        return $this->belongsTo(Prospect::class, 'related_prospect_id');

    }



    public function relatedFna(): BelongsTo

    {

        return $this->belongsTo(FnaRecord::class, 'related_fna_id');

    }



    public function checklistItems(): HasMany

    {

        return $this->hasMany(TaskUserChecklistItem::class)->orderBy('sort_order');

    }



    public function comments(): HasMany

    {

        return $this->hasMany(TaskUserComment::class)->latest();

    }



    public function scopeOpenForUser($query, User $user)

    {

        return $query

            ->where('assignee_id', $user->id)

            ->whereNotIn('status', ['completed', 'cancelled']);

    }



    public function displayTitle(): string

    {

        return $this->task?->title ?? 'Task';

    }



    public function displayDescription(): string

    {

        return trim((string) ($this->task?->description ?? ''));

    }



    public function displayNotes(): string

    {

        return trim((string) $this->additional_notes);

    }



    public function displayBody(): string

    {

        return collect([$this->displayDescription(), $this->displayNotes()])

            ->filter()

            ->implode("\n\n");

    }



    public function displayPriority(): string

    {

        return match ($this->priority) {

            'urgent' => 'Urgent',

            'high' => 'High',

            'low' => 'Low',

            default => 'Medium',

        };

    }



    public function displayStatus(): string

    {

        return match ($this->status) {

            'in_progress' => 'In Progress',

            'to_do' => 'To Do',

            'waiting' => 'Waiting',

            'completed' => 'Completed',

            'cancelled' => 'Cancelled',

            'overdue' => 'Overdue',

            default => str($this->status)->replace('_', ' ')->title()->toString(),

        };

    }



    public function displayAssignorName(): string
    {
        if (SystemTaskAssignor::isSystemAssignor($this->assignor_id)) {
            return SystemTaskAssignor::NAME;
        }

        return $this->assignor?->name ?? 'Unassigned';
    }

    public function isSystemAssigned(): bool
    {
        return SystemTaskAssignor::isSystemAssignor($this->assignor_id);
    }

    public function categoryName(): ?string

    {

        return $this->taskCategory?->name ?? $this->task?->category?->name;

    }

}


