<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('profiles', 'insurance_license_numbers')) {
                $table->json('insurance_license_numbers')->nullable()->after('insurance_licenses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('profiles', 'insurance_license_numbers')) {
                $table->dropColumn('insurance_license_numbers');
            }
        });
    }
};
