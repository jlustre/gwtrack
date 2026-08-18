<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_reactions')) {
            Schema::create('announcement_reactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('announcement_id')->constrained('message_center_announcements')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('reaction', 30);
                $table->timestamps();

                $table->unique(['announcement_id', 'user_id'], 'announcement_reaction_user_uq');
            });
        }

        if (! Schema::hasTable('announcement_comments')) {
            Schema::create('announcement_comments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('announcement_id')->constrained('message_center_announcements')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('announcement_comments')->nullOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('body');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['announcement_id', 'parent_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_comments');
        Schema::dropIfExists('announcement_reactions');
    }
};
