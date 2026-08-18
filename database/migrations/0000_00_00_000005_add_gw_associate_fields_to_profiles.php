<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('gw_associate_id')->nullable()->unique()->after('license_number');
            $table->string('gw_invite_link')->nullable()->unique()->after('gw_associate_id');
            $table->boolean('is_gw_active_associate')->default(false)->after('gw_invite_link');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['gw_associate_id', 'gw_invite_link', 'is_gw_active_associate']);
        });
    }
};
