<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\SystemTaskAssignorSeeder',
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')->where('id', 999)->delete();
    }
};
