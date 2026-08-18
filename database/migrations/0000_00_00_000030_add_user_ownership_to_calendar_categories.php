<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_categories', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('is_public')->default(false)->after('is_active');
        });

        Schema::table('calendar_categories', function (Blueprint $table): void {
            $table->dropUnique(['name']);
            $table->dropUnique(['slug']);
        });

        Schema::table('calendar_categories', function (Blueprint $table): void {
            $table->unique(['user_id', 'name']);
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_categories', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'name']);
            $table->dropUnique(['slug']);
        });

        Schema::table('calendar_categories', function (Blueprint $table): void {
            $table->unique('name');
            $table->unique('slug');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('is_public');
        });
    }
};
