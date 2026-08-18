<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->foreignUlid('related_fna_id')
                ->nullable()
                ->after('related_prospect_id');

            $table->foreign('related_fna_id')
                ->references('id')
                ->on('fna_records')
                ->nullOnDelete();

            $table->index('related_fna_id');
        });

        Schema::table('user_tasks', function (Blueprint $table): void {
            $table->foreignUlid('related_fna_id')
                ->nullable()
                ->after('related_prospect_id');

            $table->foreign('related_fna_id')
                ->references('id')
                ->on('fna_records')
                ->nullOnDelete();

            $table->index('related_fna_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_tasks', function (Blueprint $table): void {
            $table->dropForeign(['related_fna_id']);
            $table->dropColumn('related_fna_id');
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropForeign(['related_fna_id']);
            $table->dropColumn('related_fna_id');
        });
    }
};
