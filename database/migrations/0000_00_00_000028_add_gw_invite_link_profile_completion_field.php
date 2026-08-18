<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('profile_completion_fields')->where('field_key', 'gw_invite_link')->exists()) {
            return;
        }

        $associateSortOrder = DB::table('profile_completion_fields')
            ->where('field_key', 'gw_associate_id')
            ->value('sort_order');

        $sortOrder = ($associateSortOrder ?? 100) + 10;

        DB::table('profile_completion_fields')
            ->where('sort_order', '>=', $sortOrder)
            ->increment('sort_order', 10);

        DB::table('profile_completion_fields')->insert([
            'field_key' => 'gw_invite_link',
            'label' => 'GW invite link',
            'source' => 'profile',
            'sort_order' => $sortOrder,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $removedSortOrder = DB::table('profile_completion_fields')
            ->where('field_key', 'gw_invite_link')
            ->value('sort_order');

        DB::table('profile_completion_fields')
            ->where('field_key', 'gw_invite_link')
            ->delete();

        if ($removedSortOrder !== null) {
            DB::table('profile_completion_fields')
                ->where('sort_order', '>', $removedSortOrder)
                ->decrement('sort_order', 10);
        }
    }
};
