<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fna_client_invites', function (Blueprint $table): void {
            $table->foreignId('recipient_user_id')
                ->nullable()
                ->after('prospect_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['recipient_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('fna_client_invites', function (Blueprint $table): void {
            $table->dropForeign(['recipient_user_id']);
            $table->dropColumn('recipient_user_id');
        });
    }
};
