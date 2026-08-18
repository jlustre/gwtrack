<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fna_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cfm_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ulid('prospect_id')->nullable();
            $table->foreignId('calendar_event_id')->nullable()->constrained('calendar_events')->nullOnDelete();
            $table->string('status', 40)->default('draft')->index();
            $table->string('title');
            $table->string('reference_code', 20)->unique();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->unsignedTinyInteger('completeness_score')->default(0);
            $table->boolean('dime_completed')->default(false);
            $table->decimal('protection_gap', 14, 2)->nullable();
            $table->decimal('recommended_coverage_min', 14, 2)->nullable();
            $table->decimal('recommended_coverage_max', 14, 2)->nullable();
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_phone', 60)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->string('employer_business')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state_province', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('preferred_contact_method', 40)->nullable();
            $table->string('best_contact_time', 120)->nullable();
            $table->text('main_needs_identified')->nullable();
            $table->text('recommended_next_action')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('associate_recommendation')->nullable();
            $table->text('cfm_feedback_summary')->nullable();
            $table->text('summary_notes')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('presented_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['owner_user_id', 'status']);
            $table->index(['cfm_user_id', 'status']);
            $table->index(['status', 'deleted_at']);
        });

        Schema::create('fna_households', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->string('spouse_partner_name')->nullable();
            $table->unsignedTinyInteger('spouse_partner_age')->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->json('children_details')->nullable();
            $table->text('dependents_notes')->nullable();
            $table->decimal('household_income', 14, 2)->nullable();
            $table->decimal('household_expenses', 14, 2)->nullable();
            $table->json('financial_priorities')->nullable();
            $table->timestamps();

            $table->unique('fna_record_id');
        });

        Schema::create('fna_income_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->decimal('annual_income', 14, 2)->nullable();
            $table->decimal('monthly_income', 14, 2)->nullable();
            $table->decimal('spouse_annual_income', 14, 2)->nullable();
            $table->json('other_income_sources')->nullable();
            $table->decimal('business_income', 14, 2)->nullable();
            $table->decimal('passive_income', 14, 2)->nullable();
            $table->text('expected_income_changes')->nullable();
            $table->timestamps();

            $table->unique('fna_record_id');
        });

        Schema::create('fna_debt_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->decimal('mortgage_balance', 14, 2)->nullable();
            $table->decimal('rent_amount', 14, 2)->nullable();
            $table->decimal('credit_card_debt', 14, 2)->nullable();
            $table->decimal('car_loans', 14, 2)->nullable();
            $table->decimal('student_loans', 14, 2)->nullable();
            $table->decimal('personal_loans', 14, 2)->nullable();
            $table->decimal('business_debt', 14, 2)->nullable();
            $table->decimal('other_liabilities', 14, 2)->nullable();
            $table->decimal('total_debt', 14, 2)->nullable();
            $table->timestamps();

            $table->unique('fna_record_id');
        });

        Schema::create('fna_asset_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->decimal('emergency_fund', 14, 2)->nullable();
            $table->decimal('checking_savings', 14, 2)->nullable();
            $table->decimal('retirement_accounts', 14, 2)->nullable();
            $table->decimal('investment_accounts', 14, 2)->nullable();
            $table->decimal('real_estate_assets', 14, 2)->nullable();
            $table->decimal('business_assets', 14, 2)->nullable();
            $table->decimal('college_savings', 14, 2)->nullable();
            $table->decimal('other_assets', 14, 2)->nullable();
            $table->decimal('total_assets', 14, 2)->nullable();
            $table->timestamps();

            $table->unique('fna_record_id');
        });

        Schema::create('fna_existing_coverages', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->decimal('existing_life_insurance_amount', 14, 2)->nullable();
            $table->decimal('term_coverage', 14, 2)->nullable();
            $table->decimal('whole_life_coverage', 14, 2)->nullable();
            $table->decimal('universal_life_coverage', 14, 2)->nullable();
            $table->decimal('group_insurance_coverage', 14, 2)->nullable();
            $table->decimal('disability_coverage', 14, 2)->nullable();
            $table->decimal('critical_illness_coverage', 14, 2)->nullable();
            $table->decimal('long_term_care_coverage', 14, 2)->nullable();
            $table->text('beneficiary_information')->nullable();
            $table->boolean('policy_review_needed')->default(false);
            $table->timestamps();

            $table->unique('fna_record_id');
        });

        Schema::create('fna_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->json('selected_goals')->nullable();
            $table->text('goal_notes')->nullable();
            $table->timestamps();

            $table->unique('fna_record_id');
        });

        Schema::create('fna_risk_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->text('main_financial_concern')->nullable();
            $table->text('health_considerations')->nullable();
            $table->string('job_stability', 40)->nullable();
            $table->string('family_dependency_level', 40)->nullable();
            $table->string('emergency_fund_adequacy', 40)->nullable();
            $table->text('current_protection_gap')->nullable();
            $table->string('risk_tolerance', 40)->nullable();
            $table->string('urgency_level', 40)->nullable();
            $table->timestamps();

            $table->unique('fna_record_id');
        });

        Schema::create('fna_dime_analyses', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->json('debt_inputs')->nullable();
            $table->decimal('total_debt', 14, 2)->default(0);
            $table->decimal('income_annual_to_replace', 14, 2)->nullable();
            $table->unsignedSmallInteger('income_years_to_replace')->nullable();
            $table->boolean('income_inflation_adjustment')->default(true);
            $table->decimal('existing_income_replacement_coverage', 14, 2)->nullable();
            $table->decimal('total_income_need', 14, 2)->nullable();
            $table->decimal('mortgage_balance', 14, 2)->nullable();
            $table->unsignedSmallInteger('mortgage_years_remaining')->nullable();
            $table->decimal('monthly_mortgage_payment', 14, 2)->nullable();
            $table->boolean('include_mortgage_payoff')->default(true);
            $table->decimal('total_mortgage_need', 14, 2)->nullable();
            $table->unsignedTinyInteger('education_children_count')->nullable();
            $table->decimal('education_cost_per_child', 14, 2)->nullable();
            $table->unsignedSmallInteger('education_years_to_college')->nullable();
            $table->boolean('education_inflation_adjustment')->default(true);
            $table->decimal('existing_education_savings', 14, 2)->nullable();
            $table->decimal('total_education_need', 14, 2)->nullable();
            $table->decimal('total_dime_need', 14, 2)->default(0);
            $table->decimal('existing_life_insurance', 14, 2)->nullable();
            $table->decimal('liquid_assets_allocated', 14, 2)->nullable();
            $table->decimal('estimated_protection_gap', 14, 2)->default(0);
            $table->decimal('recommended_coverage_min', 14, 2)->nullable();
            $table->decimal('recommended_coverage_max', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique('fna_record_id');
        });

        Schema::create('fna_review_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('comment_type', 40)->default('coaching');
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index('fna_record_id');
        });

        Schema::create('fna_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('change_source', 40)->default('manual');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('fna_record_id');
        });

        Schema::create('fna_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->string('category', 60)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fna_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission_level', 40)->default('view');
            $table->boolean('can_view_financial_details')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['fna_record_id', 'shared_with_user_id', 'status'], 'fna_permissions_share_idx');
        });

        Schema::create('fna_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('fna_record_id')->constrained('fna_records')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index('fna_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fna_activity_logs');
        Schema::dropIfExists('fna_permissions');
        Schema::dropIfExists('fna_attachments');
        Schema::dropIfExists('fna_status_histories');
        Schema::dropIfExists('fna_review_comments');
        Schema::dropIfExists('fna_dime_analyses');
        Schema::dropIfExists('fna_risk_assessments');
        Schema::dropIfExists('fna_goals');
        Schema::dropIfExists('fna_existing_coverages');
        Schema::dropIfExists('fna_asset_details');
        Schema::dropIfExists('fna_debt_details');
        Schema::dropIfExists('fna_income_details');
        Schema::dropIfExists('fna_households');
        Schema::dropIfExists('fna_records');
    }
};
