<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('profiles', 'gw_invite_link')) {
                $table->string('gw_invite_link', 2048)->nullable()->after('gw_associate_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('profiles', 'gw_invite_link')) {
                $table->dropColumn('gw_invite_link');
            }
        });
    }
};
