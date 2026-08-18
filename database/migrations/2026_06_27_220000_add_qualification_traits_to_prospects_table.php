<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            if (! Schema::hasColumn('prospects', 'qualification_traits')) {
                $table->json('qualification_traits')->nullable()->after('notes_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            if (Schema::hasColumn('prospects', 'qualification_traits')) {
                $table->dropColumn('qualification_traits');
            }
        });
    }
};
