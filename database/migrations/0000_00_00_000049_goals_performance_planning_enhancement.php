<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('goals', 'goal_type')) {
            Schema::table('goals', function (Blueprint $table): void {
                $table->string('goal_type', 32)->default('outcome')->after('hierarchy_level');
                $table->string('planning_type', 64)->nullable()->after('goal_type');
                $table->string('funnel_stage_key', 64)->nullable()->after('planning_type');
                $table->foreignId('blueprint_id')->nullable()->after('funnel_stage_key');
                $table->decimal('contribution_weight', 5, 2)->nullable()->after('blueprint_id');
            });
        }

        if (! Schema::hasTable('goal_blueprints')) {
            Schema::create('goal_blueprints', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('planning_type', 64);
                $table->string('name');
                $table->string('period_type', 32)->default('annual');
                $table->decimal('root_target_value', 14, 2);
                $table->string('status', 32)->default('active');
                $table->json('funnel_snapshot')->nullable();
                $table->json('conversion_snapshot')->nullable();
                $table->date('starts_at')->nullable();
                $table->date('deadline_at')->nullable();
                $table->foreignId('root_goal_id')->nullable()->constrained('goals')->nullOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (Schema::hasColumn('goals', 'blueprint_id') && ! $this->hasForeignKey('goals', 'goals_blueprint_id_foreign')) {
            Schema::table('goals', function (Blueprint $table): void {
                $table->foreign('blueprint_id')->references('id')->on('goal_blueprints')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('goal_dependencies')) {
            Schema::create('goal_dependencies', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('parent_goal_id')->constrained('goals')->cascadeOnDelete();
                $table->foreignId('child_goal_id')->constrained('goals')->cascadeOnDelete();
                $table->string('relationship_type', 32)->default('requires');
                $table->decimal('contribution_percent', 5, 2)->default(100);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['parent_goal_id', 'child_goal_id'], 'goal_dependencies_unique');
            });
        }

        if (! Schema::hasTable('goal_conversion_rates')) {
            Schema::create('goal_conversion_rates', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('funnel_key', 64);
                $table->string('from_stage', 64);
                $table->string('to_stage', 64);
                $table->decimal('rate', 8, 4);
                $table->unsignedInteger('sample_size')->default(0);
                $table->timestamp('calculated_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'funnel_key', 'from_stage', 'to_stage'], 'goal_conv_rates_unique');
            });
        }

        if (! Schema::hasTable('goal_activity_targets')) {
            Schema::create('goal_activity_targets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
                $table->string('activity_key', 64);
                $table->string('period_type', 32);
                $table->decimal('target_value', 14, 2)->default(0);
                $table->decimal('actual_value', 14, 2)->default(0);
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->timestamps();

                $table->unique(['goal_id', 'activity_key', 'period_type', 'period_start'], 'goal_activity_targets_unique');
            });
        }

        if (! Schema::hasTable('goal_activity_logs')) {
            Schema::create('goal_activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('goal_id')->nullable()->constrained('goals')->nullOnDelete();
                $table->string('activity_key', 64);
                $table->decimal('value', 14, 2)->default(1);
                $table->date('logged_for_date');
                $table->string('source', 32)->default('manual');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'activity_key', 'logged_for_date'], 'goal_activity_logs_lookup');
            });
        }

        if (! Schema::hasTable('goal_recommendations')) {
            Schema::create('goal_recommendations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('goal_id')->nullable()->constrained('goals')->nullOnDelete();
                $table->string('recommendation_type', 64);
                $table->string('priority', 16)->default('medium');
                $table->text('message');
                $table->json('action_payload')->nullable();
                $table->timestamp('dismissed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'dismissed_at'], 'goal_recommendations_user_idx');
            });
        }

        if (! Schema::hasTable('goal_simulations')) {
            Schema::create('goal_simulations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('scenario_type', 64);
                $table->string('name')->nullable();
                $table->json('inputs');
                $table->json('results');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('goal_alerts')) {
            Schema::create('goal_alerts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('goal_id')->nullable()->constrained('goals')->nullOnDelete();
                $table->string('alert_type', 64);
                $table->string('severity', 16)->default('warning');
                $table->string('title');
                $table->text('message');
                $table->timestamp('triggered_at');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'read_at', 'resolved_at'], 'goal_alerts_user_idx');
            });
        }

        if (! Schema::hasTable('goal_coaching_sessions')) {
            Schema::create('goal_coaching_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('coach_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('trainee_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('goal_id')->nullable()->constrained('goals')->nullOnDelete();
                $table->text('summary')->nullable();
                $table->json('action_items')->nullable();
                $table->timestamp('session_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('goal_forecasts', 'projected_percent')) {
            Schema::table('goal_forecasts', function (Blueprint $table): void {
                $table->decimal('projected_percent', 5, 2)->nullable()->after('projected_value');
                $table->json('recommended_actions')->nullable()->after('notes');
                $table->string('pace_status', 32)->nullable()->after('recommended_actions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('goal_forecasts', 'projected_percent')) {
            Schema::table('goal_forecasts', function (Blueprint $table): void {
                $table->dropColumn(['projected_percent', 'recommended_actions', 'pace_status']);
            });
        }

        Schema::dropIfExists('goal_coaching_sessions');
        Schema::dropIfExists('goal_alerts');
        Schema::dropIfExists('goal_simulations');
        Schema::dropIfExists('goal_recommendations');
        Schema::dropIfExists('goal_activity_logs');
        Schema::dropIfExists('goal_activity_targets');
        Schema::dropIfExists('goal_conversion_rates');
        Schema::dropIfExists('goal_dependencies');

        if (Schema::hasColumn('goals', 'blueprint_id')) {
            Schema::table('goals', function (Blueprint $table): void {
                if ($this->hasForeignKey('goals', 'goals_blueprint_id_foreign')) {
                    $table->dropForeign(['blueprint_id']);
                }
                $table->dropColumn(['goal_type', 'planning_type', 'funnel_stage_key', 'blueprint_id', 'contribution_weight']);
            });
        }

        Schema::dropIfExists('goal_blueprints');
    }

    private function hasForeignKey(string $table, string $foreignKey): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return false;
        }

        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$database, $table, $foreignKey, 'FOREIGN KEY']
        );

        return $result !== null;
    }
};
