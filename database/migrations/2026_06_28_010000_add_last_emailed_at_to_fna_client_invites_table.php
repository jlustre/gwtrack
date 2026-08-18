<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fna_client_invites', function (Blueprint $table): void {
            if (! Schema::hasColumn('fna_client_invites', 'last_emailed_at')) {
                $table->timestamp('last_emailed_at')->nullable()->after('personal_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fna_client_invites', function (Blueprint $table): void {
            if (Schema::hasColumn('fna_client_invites', 'last_emailed_at')) {
                $table->dropColumn('last_emailed_at');
            }
        });
    }
};
