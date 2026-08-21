<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('billing_interval', 20)->default('monthly')->after('pending_rank_code');
            $table->string('pending_billing_interval', 20)->nullable()->after('billing_interval');
            $table->boolean('auto_pay')->default(false)->after('pending_billing_interval');
            $table->string('auto_pay_brand', 20)->nullable()->after('auto_pay');
            $table->string('auto_pay_last_four', 4)->nullable()->after('auto_pay_brand');
        });

        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->string('billing_interval', 20)->default('monthly')->after('rank_label');
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'billing_interval',
                'pending_billing_interval',
                'auto_pay',
                'auto_pay_brand',
                'auto_pay_last_four',
            ]);
        });

        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropColumn('billing_interval');
        });
    }
};
