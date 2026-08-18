<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resources')) {
            return;
        }

        if (! Schema::hasColumn('resources', 'content')) {
            Schema::table('resources', function (Blueprint $table): void {
                $table->longText('content')->nullable();
            });
        }

        if (! Schema::hasColumn('resources', 'pdf_generated_at')) {
            Schema::table('resources', function (Blueprint $table): void {
                $table->timestamp('pdf_generated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('resources')) {
            return;
        }

        $columns = array_values(array_filter(
            ['content', 'pdf_generated_at'],
            fn (string $column): bool => Schema::hasColumn('resources', $column),
        ));

        if ($columns !== []) {
            Schema::table('resources', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
