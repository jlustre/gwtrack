<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy cfm_trainee_checklist_* tables consolidated into checklists / checklist_progress.
    }

    public function down(): void
    {
        //
    }
};
