<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('billed_rank_code', 20)->nullable();
            $table->string('pending_rank_code', 20)->nullable();
            $table->unsignedTinyInteger('anniversary_day')->nullable();
            $table->date('started_at')->nullable();
            $table->date('next_billing_at')->nullable();
            $table->timestamp('last_anniversary_applied_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('next_billing_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
