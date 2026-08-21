<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('profiles', 'subscription_terms_accepted_at')) {
                $table->timestamp('subscription_terms_accepted_at')->nullable()->after('is_gw_active_associate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('profiles', 'subscription_terms_accepted_at')) {
                $table->dropColumn('subscription_terms_accepted_at');
            }
        });
    }
};
