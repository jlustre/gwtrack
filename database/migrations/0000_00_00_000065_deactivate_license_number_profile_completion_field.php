<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profile_completion_fields')) {
            return;
        }

        DB::table('profile_completion_fields')
            ->where('field_key', 'license_number')
            ->update([
                'is_active' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('profile_completion_fields')) {
            return;
        }

        DB::table('profile_completion_fields')
            ->where('field_key', 'license_number')
            ->update([
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
    }
};
