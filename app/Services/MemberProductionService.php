<?php

namespace App\Services;

use App\Models\MemberProductionEntry;
use App\Models\User;
use Illuminate\Support\Carbon;

class MemberProductionService
{
    /**
     * @param  array{description: string, policy_reference?: string|null, annual_premium: float|string, posted_at?: string|null}  $data
     */
    public function createForMember(User $member, User $enteredBy, array $data): MemberProductionEntry
    {
        return MemberProductionEntry::query()->create([
            'user_id' => $member->id,
            'source' => 'manual',
            'policy_reference' => filled($data['policy_reference'] ?? null) ? $data['policy_reference'] : null,
            'description' => $data['description'],
            'annual_premium' => $data['annual_premium'],
            'status' => 'posted',
            'posted_at' => isset($data['posted_at']) && filled($data['posted_at'])
                ? Carbon::parse($data['posted_at'])->toDateString()
                : now()->toDateString(),
            'metadata' => [
                'entered_by_user_id' => $enteredBy->id,
            ],
        ]);
    }
}
