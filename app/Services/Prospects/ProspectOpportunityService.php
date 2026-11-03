<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\RecruitingOpportunity;
use App\Models\SalesOpportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProspectOpportunityService
{
    public function __construct(private readonly CrmRelationshipExpansionService $relationships) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{sales: SalesOpportunity|null, recruiting: RecruitingOpportunity|null}
     */
    public function saveForecast(Prospect $prospect, User $actor, array $attributes): array
    {
        abort_unless($actor->can('update', $prospect), 403);

        $validated = Validator::make($attributes, [
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'sales_probability' => ['nullable', 'integer', 'between:0,100'],
            'expected_close_at' => ['nullable', 'date'],
            'recruiting_probability' => ['nullable', 'integer', 'between:0,100'],
            'expected_start_at' => ['nullable', 'date'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($prospect, $validated): array {
            $lockedProspect = Prospect::query()->whereKey($prospect->id)->lockForUpdate()->firstOrFail();
            $records = $this->relationships->syncProspect($lockedProspect);
            $sales = $records['sales_opportunity'];
            $recruiting = $records['recruiting_opportunity'];
            $lostReason = array_key_exists('lost_reason', $validated)
                ? $validated['lost_reason']
                : $lockedProspect->lost_reason;

            if ($sales) {
                $sales->forceFill([
                    'estimated_value' => array_key_exists('estimated_value', $validated)
                        ? $validated['estimated_value']
                        : $sales->estimated_value,
                    'expected_close_at' => array_key_exists('expected_close_at', $validated)
                        ? $validated['expected_close_at']
                        : $sales->expected_close_at,
                    'lost_reason' => $lostReason,
                ]);
                if (array_key_exists('sales_probability', $validated) && $validated['sales_probability'] !== null) {
                    $sales->probability = $validated['sales_probability'];
                }
                $sales->save();
            }

            if ($recruiting) {
                $recruiting->forceFill([
                    'expected_start_at' => array_key_exists('expected_start_at', $validated)
                        ? $validated['expected_start_at']
                        : $recruiting->expected_start_at,
                    'lost_reason' => $lostReason,
                ]);
                if (array_key_exists('recruiting_probability', $validated) && $validated['recruiting_probability'] !== null) {
                    $recruiting->probability = $validated['recruiting_probability'];
                }
                $recruiting->save();
            }

            $lockedProspect->forceFill(['lost_reason' => $lostReason])->save();
            $this->relationships->syncProspect($lockedProspect->fresh());

            return [
                'sales' => $lockedProspect->fresh('salesOpportunity')->salesOpportunity,
                'recruiting' => $lockedProspect->fresh('recruitingOpportunity')->recruitingOpportunity,
            ];
        });
    }
}
