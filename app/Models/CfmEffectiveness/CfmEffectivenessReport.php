<?php

namespace App\Models\CfmEffectiveness;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfmEffectivenessReport extends Model
{
    public const TYPES = [
        'effectiveness_summary',
        'quarterly_mentor',
        'retention_report',
        'licensing_report',
        'fap_report',
        'mentor_comparison',
    ];

    public const AUDIENCES = [
        'cfm',
        'agency_owner',
        'leadership',
    ];

    public const PERIODS = [
        'monthly',
        'quarterly',
        'annual',
    ];

    protected $fillable = [
        'cfm_id',
        'trainee_id',
        'report_type',
        'audience',
        'period_type',
        'period_start',
        'period_end',
        'payload',
        'export_format',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function cfm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfm_id');
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function typeLabel(): string
    {
        return config('cfm-effectiveness.report_types.'.$this->report_type, ucfirst(str_replace('_', ' ', $this->report_type)));
    }
}
