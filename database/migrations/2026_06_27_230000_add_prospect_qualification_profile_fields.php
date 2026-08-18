<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            if (! Schema::hasColumn('prospects', 'spouse_name')) {
                $table->string('spouse_name')->nullable()->after('marital_status');
            }

            if (! Schema::hasColumn('prospects', 'spouse_occupation')) {
                $table->string('spouse_occupation')->nullable()->after('spouse_name');
            }

            if (! Schema::hasColumn('prospects', 'spouse_date_of_birth')) {
                $table->date('spouse_date_of_birth')->nullable()->after('spouse_occupation');
            }

            if (! Schema::hasColumn('prospects', 'dependents')) {
                $table->json('dependents')->nullable()->after('children_count');
            }

            if (! Schema::hasColumn('prospects', 'qualification_notes')) {
                $table->text('qualification_notes')->nullable()->after('qualification_traits');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            foreach (['spouse_name', 'spouse_occupation', 'spouse_date_of_birth', 'dependents', 'qualification_notes'] as $column) {
                if (Schema::hasColumn('prospects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
