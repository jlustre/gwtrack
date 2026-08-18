<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy user_onboarding_progress confirmation fields live on checklist_progress.
    }

    public function down(): void
    {
        //
    }
};
