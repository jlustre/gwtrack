<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_checklist_type_starts', function (Blueprint $table): void {
            $table->foreignId('started_by')
                ->nullable()
                ->after('started_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_checklist_type_starts', function (Blueprint $table): void {
            $table->dropForeign(['started_by']);
            $table->dropColumn('started_by');
        });
    }
};
