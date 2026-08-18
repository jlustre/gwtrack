<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_appointments', function (Blueprint $table): void {
            $table->foreignId('calendar_event_id')
                ->nullable()
                ->after('appointment_type_id')
                ->constrained('calendar_events')
                ->nullOnDelete();
        });

        Schema::table('user_tasks', function (Blueprint $table): void {
            $table->ulid('related_prospect_id')
                ->nullable()
                ->after('related_person');

            $table->foreign('related_prospect_id')
                ->references('id')
                ->on('prospects')
                ->nullOnDelete();

            $table->index('related_prospect_id');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_appointments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('calendar_event_id');
        });

        Schema::table('user_tasks', function (Blueprint $table): void {
            $table->dropForeign(['related_prospect_id']);
            $table->dropColumn('related_prospect_id');
        });
    }
};
