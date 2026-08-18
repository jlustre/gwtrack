<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_device_tokens')) {
            return;
        }

        Schema::table('notification_device_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('notification_device_tokens', 'subscription_payload')) {
                $table->text('subscription_payload')->nullable()->after('token');
            }
        });

        if (Schema::hasColumn('notification_device_tokens', 'subscription_payload')) {
            $this->migrateExistingWebTokens();
        }

        $this->replaceTokenUniqueIndex();
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_device_tokens')) {
            return;
        }

        Schema::table('notification_device_tokens', function (Blueprint $table): void {
            if (Schema::hasColumn('notification_device_tokens', 'subscription_payload')) {
                $table->dropColumn('subscription_payload');
            }
        });
    }

    private function migrateExistingWebTokens(): void
    {
        DB::table('notification_device_tokens')
            ->where('platform', 'web')
            ->whereNull('subscription_payload')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    if (! is_string($row->token) || $row->token === '') {
                        continue;
                    }

                    if (str_starts_with(trim($row->token), '{')) {
                        DB::table('notification_device_tokens')
                            ->where('id', $row->id)
                            ->update(['subscription_payload' => $row->token]);
                    }
                }
            });
    }

    private function replaceTokenUniqueIndex(): void
    {
        try {
            Schema::table('notification_device_tokens', function (Blueprint $table): void {
                $table->dropUnique(['user_id', 'token']);
            });
        } catch (\Throwable) {
            // Index may not exist in some environments.
        }

        Schema::table('notification_device_tokens', function (Blueprint $table): void {
            $table->unique(['user_id', 'token']);
        });
    }
};
