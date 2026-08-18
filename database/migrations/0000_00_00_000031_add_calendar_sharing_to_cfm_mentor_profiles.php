<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfm_mentor_profiles', function (Blueprint $table): void {
            $table->boolean('share_calendar_with_apprentices')->default(true)->after('manual_unavailable');
            $table->boolean('share_calendar_with_agency_owner')->default(false)->after('share_calendar_with_apprentices');
        });
    }

    public function down(): void
    {
        Schema::table('cfm_mentor_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'share_calendar_with_apprentices',
                'share_calendar_with_agency_owner',
            ]);
        });
    }
};
