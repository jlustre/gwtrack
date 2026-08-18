<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profile_completion_fields')) {
            Schema::create('profile_completion_fields', function (Blueprint $table) {
                $table->id();
                $table->string('field_key', 60);
                $table->string('label');
                $table->string('source', 20)->default('profile');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique('field_key');
            });

            return;
        }

        Schema::table('profile_completion_fields', function (Blueprint $table): void {
            if (! Schema::hasColumn('profile_completion_fields', 'source')) {
                $table->string('source', 20)->default('profile')->after('label');
            }

            if (! Schema::hasColumn('profile_completion_fields', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('profile_completion_fields')) {
            return;
        }

        Schema::table('profile_completion_fields', function (Blueprint $table): void {
            if (Schema::hasColumn('profile_completion_fields', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if (Schema::hasColumn('profile_completion_fields', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
