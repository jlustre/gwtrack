<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_badges')) {
            Schema::create('user_badges', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
                $table->foreignId('announcement_id')->nullable()->constrained('message_center_announcements')->nullOnDelete();
                $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('awarded_at');
                $table->timestamps();

                $table->unique(['user_id', 'badge_id', 'announcement_id'], 'user_badge_announcement_uq');
            });
        }

        if (Schema::hasTable('badges')) {
            Schema::table('badges', function (Blueprint $table): void {
                if (! Schema::hasColumn('badges', 'category')) {
                    $table->string('category', 40)->default('recognition')->after('slug');
                }
                if (! Schema::hasColumn('badges', 'color')) {
                    $table->string('color', 20)->nullable()->after('category');
                }
                if (! Schema::hasColumn('badges', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('color');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');

        if (Schema::hasTable('badges')) {
            Schema::table('badges', function (Blueprint $table): void {
                foreach (['category', 'color', 'is_active'] as $column) {
                    if (Schema::hasColumn('badges', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
