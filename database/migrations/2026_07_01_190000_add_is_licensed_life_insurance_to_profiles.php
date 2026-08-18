<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('profiles', 'is_licensed_life_insurance')) {
                $table->boolean('is_licensed_life_insurance')
                    ->default(false)
                    ->after('license_number');
            }
        });

        if (Schema::hasColumn('profiles', 'insurance_licenses')) {
            DB::table('profiles')
                ->whereNotNull('insurance_licenses')
                ->where('insurance_licenses', '!=', '[]')
                ->where('insurance_licenses', '!=', 'null')
                ->update(['is_licensed_life_insurance' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('profiles', 'is_licensed_life_insurance')) {
                $table->dropColumn('is_licensed_life_insurance');
            }
        });
    }
};
