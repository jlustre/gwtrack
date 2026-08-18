<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_quotes') || Schema::hasColumn('daily_quotes', 'deleted_at')) {
            return;
        }

        Schema::table('daily_quotes', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_quotes') || ! Schema::hasColumn('daily_quotes', 'deleted_at')) {
            return;
        }

        Schema::table('daily_quotes', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
