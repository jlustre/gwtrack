<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rank_requirements', function (Blueprint $table): void {
            $table->string('code', 160)->nullable()->after('rank_id');
            $table->boolean('is_active')->default(true)->after('is_required');
        });

        $usedCodes = [];
        DB::table('rank_requirements')->orderBy('id')->get(['id', 'rank_id', 'title'])->each(function (object $requirement) use (&$usedCodes): void {
            $baseCode = substr(Str::slug($requirement->title) ?: 'requirement', 0, 140);
            $code = $baseCode;
            $key = $requirement->rank_id.':'.$code;
            $collisionIndex = 0;

            while (isset($usedCodes[$key])) {
                $suffix = '-'.$requirement->id.($collisionIndex > 0 ? '-'.$collisionIndex : '');
                $code = substr($baseCode, 0, 160 - strlen($suffix)).$suffix;
                $key = $requirement->rank_id.':'.$code;
                $collisionIndex++;
            }

            $usedCodes[$key] = true;
            DB::table('rank_requirements')->where('id', $requirement->id)->update(['code' => $code]);
        });

        Schema::table('rank_requirements', function (Blueprint $table): void {
            $table->unique(['rank_id', 'code'], 'rank_requirements_rank_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('rank_requirements', function (Blueprint $table): void {
            $table->dropUnique('rank_requirements_rank_code_unique');
            $table->dropColumn(['code', 'is_active']);
        });
    }
};
