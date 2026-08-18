<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfm_mentor_profiles', function (Blueprint $table): void {
            $table->json('licensed_jurisdictions')->nullable()->after('specialties');
        });
    }

    public function down(): void
    {
        Schema::table('cfm_mentor_profiles', function (Blueprint $table): void {
            $table->dropColumn('licensed_jurisdictions');
        });
    }
};
