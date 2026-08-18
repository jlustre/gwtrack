<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_assignments', function (Blueprint $table): void {
            $table->timestamp('confirmed_at')->nullable()->after('started_at');
            $table->timestamp('first_contact_sent_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('mentor_assignments', function (Blueprint $table): void {
            $table->dropColumn(['confirmed_at', 'first_contact_sent_at']);
        });
    }
};
