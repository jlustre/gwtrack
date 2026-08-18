<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $personalId = DB::table('calendar_categories')
            ->where('slug', 'personal')
            ->whereNull('user_id')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->value('id');

        if (! $personalId) {
            return;
        }

        $encoded = json_encode([(int) $personalId]);

        DB::table('user_calendar_preferences')->update([
            'visible_calendar_categories' => $encoded,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Intentionally left blank — previous per-user selections cannot be restored.
    }
};
