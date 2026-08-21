<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $table->string('track', 20)->default('producer')->after('description');
            $table->string('income_metric', 40)->nullable()->after('track');
            $table->unsignedInteger('income_requirement')->nullable()->after('income_metric');
            $table->string('qualification')->nullable()->after('income_requirement');
            $table->unsignedSmallInteger('nba_requirement')->nullable()->after('qualification');
            $table->unsignedSmallInteger('mb_leg_requirement')->nullable()->after('nba_requirement');
            $table->unsignedSmallInteger('svp_leg_requirement')->nullable()->after('mb_leg_requirement');
            $table->unsignedSmallInteger('svp_leg_alternate')->nullable()->after('svp_leg_requirement');
            $table->decimal('compensation_rate', 6, 2)->nullable()->after('svp_leg_alternate');
            $table->decimal('ppb_rate', 6, 2)->nullable()->after('compensation_rate');
            $table->decimal('total_fyo_rate', 6, 2)->nullable()->after('ppb_rate');
            $table->decimal('non_fyc_rate', 6, 2)->nullable()->after('total_fyo_rate');
            $table->boolean('is_field_leader')->default(false)->after('non_fyc_rate');
        });
    }

    public function down(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $table->dropColumn([
                'track',
                'income_metric',
                'income_requirement',
                'qualification',
                'nba_requirement',
                'mb_leg_requirement',
                'svp_leg_requirement',
                'svp_leg_alternate',
                'compensation_rate',
                'ppb_rate',
                'total_fyo_rate',
                'non_fyc_rate',
                'is_field_leader',
            ]);
        });
    }
};
