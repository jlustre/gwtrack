<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('description');
            $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            $table->string('payment_method', 30)->nullable()->after('paid_by');
            $table->string('payment_reference', 80)->nullable()->after('payment_method');
            $table->text('payment_notes')->nullable()->after('payment_reference');

            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropIndex(['status', 'paid_at']);
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn([
                'paid_at',
                'payment_method',
                'payment_reference',
                'payment_notes',
            ]);
        });
    }
};
