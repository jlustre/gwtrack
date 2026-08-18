<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('messaging_suspended_at')->nullable()->after('is_online');
            $table->foreignId('messaging_suspended_by')->nullable()->after('messaging_suspended_at')->constrained('users')->nullOnDelete();
            $table->text('messaging_suspension_reason')->nullable()->after('messaging_suspended_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('messaging_suspended_by');
            $table->dropColumn([
                'messaging_suspended_at',
                'messaging_suspension_reason',
            ]);
        });
    }
};
