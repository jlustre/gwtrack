<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_subscription_id')->nullable()->constrained('user_subscriptions')->nullOnDelete();
            $table->string('number', 40)->unique();
            $table->string('rank_code', 20);
            $table->string('rank_label');
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('issued');
            $table->date('billed_at');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_start']);
            $table->index(['user_id', 'billed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
