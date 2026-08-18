<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prospect_funnels')) {
            Schema::create('prospect_funnels', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('key', 40)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['user_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('prospect_funnel_stages')) {
            Schema::create('prospect_funnel_stages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('prospect_funnel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pipeline_stage_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->decimal('conversion_weight', 5, 2)->default(0);
                $table->boolean('is_terminal')->default(false);
                $table->json('auto_task_template')->nullable();
                $table->timestamps();
                $table->unique(['prospect_funnel_id', 'slug']);
                $table->index(['prospect_funnel_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('prospect_stage_history')) {
            Schema::create('prospect_stage_history', function (Blueprint $table): void {
                $table->id();
                $table->foreignUlid('prospect_id')->constrained('prospects')->cascadeOnDelete();
                $table->foreignId('from_stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
                $table->foreignId('to_stage_id')->nullable()->constrained('pipeline_stages')->nullOnDelete();
                $table->foreignId('from_funnel_id')->nullable()->constrained('prospect_funnels')->nullOnDelete();
                $table->foreignId('to_funnel_id')->nullable()->constrained('prospect_funnels')->nullOnDelete();
                $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
                $table->string('change_source', 40)->default('manual');
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['prospect_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('prospect_activities')) {
            Schema::create('prospect_activities', function (Blueprint $table): void {
                $table->id();
                $table->foreignUlid('prospect_id')->constrained('prospects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('activity_type', 60);
                $table->timestamp('occurred_at');
                $table->unsignedSmallInteger('duration_minutes')->nullable();
                $table->string('outcome', 80)->nullable();
                $table->text('notes')->nullable();
                $table->text('next_action')->nullable();
                $table->timestamp('next_follow_up_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['prospect_id', 'occurred_at']);
                $table->index(['user_id', 'occurred_at']);
            });
        } else {
            Schema::table('prospect_activities', function (Blueprint $table): void {
                if (! Schema::hasColumn('prospect_activities', 'duration_minutes')) {
                    $table->unsignedSmallInteger('duration_minutes')->nullable()->after('occurred_at');
                }

                if (! Schema::hasColumn('prospect_activities', 'metadata')) {
                    $table->json('metadata')->nullable()->after('next_follow_up_at');
                }

                if (! Schema::hasColumn('prospect_activities', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        Schema::table('prospects', function (Blueprint $table): void {
            if (! Schema::hasColumn('prospects', 'funnel_type')) {
                $table->string('funnel_type', 20)->default('insurance')->after('pipeline_stage_id');
            }

            if (! Schema::hasColumn('prospects', 'prospect_funnel_id')) {
                $table->foreignId('prospect_funnel_id')->nullable()->after('funnel_type')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('prospects', 'interest_score')) {
                $table->unsignedTinyInteger('interest_score')->nullable()->after('interest_level');
            }

            if (! Schema::hasColumn('prospects', 'fna_status')) {
                $table->string('fna_status', 40)->nullable()->after('interest_score');
            }

            if (! Schema::hasColumn('prospects', 'visibility_preset')) {
                $table->string('visibility_preset', 40)->default('private')->after('fna_status');
            }

            if (! Schema::hasColumn('prospects', 'referral_source_name')) {
                $table->string('referral_source_name')->nullable()->after('visibility_preset');
            }

            if (! Schema::hasColumn('prospects', 'campaign_name')) {
                $table->string('campaign_name')->nullable()->after('referral_source_name');
            }

            if (! Schema::hasColumn('prospects', 'event_name')) {
                $table->string('event_name')->nullable()->after('campaign_name');
            }

            if (! Schema::hasColumn('prospects', 'social_source')) {
                $table->string('social_source', 120)->nullable()->after('event_name');
            }

            if (! Schema::hasColumn('prospects', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('city');
            }

            if (! Schema::hasColumn('prospects', 'postal_code')) {
                $table->string('postal_code', 40)->nullable()->after('state_province');
            }

            if (! Schema::hasColumn('prospects', 'home_phone')) {
                $table->string('home_phone', 60)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('prospects', 'work_phone')) {
                $table->string('work_phone', 60)->nullable()->after('home_phone');
            }

            if (! Schema::hasColumn('prospects', 'engagement_score')) {
                $table->decimal('engagement_score', 5, 2)->default(0)->after('work_phone');
            }

            if (! Schema::hasColumn('prospects', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('last_contacted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table): void {
            if (Schema::hasColumn('prospects', 'prospect_funnel_id')) {
                $table->dropForeign(['prospect_funnel_id']);
            }

            $columns = array_filter([
                'funnel_type',
                'prospect_funnel_id',
                'interest_score',
                'fna_status',
                'visibility_preset',
                'referral_source_name',
                'campaign_name',
                'event_name',
                'social_source',
                'address_line_1',
                'postal_code',
                'home_phone',
                'work_phone',
                'engagement_score',
                'last_activity_at',
            ], fn (string $column) => Schema::hasColumn('prospects', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::dropIfExists('prospect_activities');
        Schema::dropIfExists('prospect_stage_history');
        Schema::dropIfExists('prospect_funnel_stages');
        Schema::dropIfExists('prospect_funnels');
    }
};
