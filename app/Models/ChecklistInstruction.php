<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistInstruction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'checklist_type_id',
        'instructions',
        'doc_link',
        'other_link',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'checklist_type_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function checklistType(): BelongsTo
    {
        return $this->belongsTo(ChecklistType::class);
    }
}
