<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;

class TaskCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'action_route',
        'action_url',
        'action_label',
        'icon',
        'accent_class',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'task_category_id');
    }

    public function taskUsers(): HasMany
    {
        return $this->hasMany(TaskUser::class, 'task_category_id');
    }

    public function resolveActionUrl(): ?string
    {
        if (filled($this->action_url)) {
            return $this->action_url;
        }

        if (filled($this->action_route) && Route::has($this->action_route)) {
            return route($this->action_route);
        }

        return null;
    }

    /**
     * @return array{url: string|null, label: string}
     */
    public function actionLink(): array
    {
        return [
            'url' => $this->resolveActionUrl(),
            'label' => filled($this->action_label) ? $this->action_label : 'Open',
        ];
    }
}
