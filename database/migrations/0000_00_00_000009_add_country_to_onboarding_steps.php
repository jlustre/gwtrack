<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy onboarding_steps.country moved to unified checklists table.
    }

    public function down(): void
    {
        //
    }
};
