<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Legacy pre-employment and BP employee tables were removed.
 * Retained as a no-op migration to preserve migration order.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
