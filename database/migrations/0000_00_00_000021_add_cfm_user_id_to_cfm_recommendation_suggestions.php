<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfm_recommendation_suggestions', function (Blueprint $table): void {
            $table->foreignId('cfm_user_id')->nullable()->after('recommendation_type')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cfm_recommendation_suggestions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cfm_user_id');
        });
    }
};
