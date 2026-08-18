<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileCompletionField extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'field_key',
        'label',
        'source',
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

    /**
     * @return array<string, array{label: string, source: string}>
     */
    public static function definitions(): array
    {
        return [
            'name' => ['label' => 'Full name', 'source' => 'user'],
            'email' => ['label' => 'Email address', 'source' => 'user'],
            'phone' => ['label' => 'Phone number', 'source' => 'profile'],
            'city' => ['label' => 'City', 'source' => 'profile'],
            'state_province_id' => ['label' => 'Province / state', 'source' => 'profile'],
            'country_id' => ['label' => 'Country', 'source' => 'profile'],
            'timezone_id' => ['label' => 'Timezone', 'source' => 'profile'],
            'best_contact_time' => ['label' => 'Best contact time', 'source' => 'profile'],
            'gw_associate_id' => ['label' => 'GW associate ID', 'source' => 'profile'],
            'gw_invite_link' => ['label' => 'GW invite link', 'source' => 'profile'],
            'bio' => ['label' => 'Member bio', 'source' => 'profile'],
            'profile_photo_path' => ['label' => 'Profile photo', 'source' => 'profile'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fieldKeyOptions(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition, string $key): array => [
                $key => $definition['label'].' ('.$key.')',
            ])
            ->all();
    }
}
