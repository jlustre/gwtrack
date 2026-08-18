<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_invitations', function (Blueprint $table): void {
            $table->ulid('prospect_id')
                ->nullable()
                ->after('accepted_by');

            $table->foreign('prospect_id')
                ->references('id')
                ->on('prospects')
                ->nullOnDelete();

            $table->index('prospect_id');
        });
    }

    public function down(): void
    {
        Schema::table('registration_invitations', function (Blueprint $table): void {
            $table->dropForeign(['prospect_id']);
            $table->dropIndex(['prospect_id']);
            $table->dropColumn('prospect_id');
        });
    }
};
