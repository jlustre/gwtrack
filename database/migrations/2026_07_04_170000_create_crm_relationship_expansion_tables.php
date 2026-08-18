<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->ulid('source_prospect_id')->nullable()->unique();
            $table->string('contact_type', 40)->default('prospect')->index();
            $table->string('relationship_status', 40)->default('active')->index();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('secondary_phone')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('country')->nullable();
            $table->string('timezone')->nullable();
            $table->string('preferred_language')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer_business')->nullable();
            $table->string('preferred_contact_method', 40)->nullable();
            $table->string('best_contact_time', 120)->nullable();
            $table->text('notes_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('source_prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['owner_id', 'contact_type', 'relationship_status'], 'contacts_owner_type_status_idx');
        });

        Schema::create('sales_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->ulid('contact_id')->nullable();
            $table->ulid('prospect_id')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prospect_funnel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained('calendar_events')->nullOnDelete();
            $table->string('title');
            $table->string('status', 40)->default('open')->index();
            $table->string('opportunity_type', 60)->default('insurance');
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['owner_id', 'status', 'expected_close_at'], 'sales_opps_owner_status_close_idx');
        });

        Schema::create('recruiting_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->ulid('contact_id')->nullable();
            $table->ulid('prospect_id')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prospect_funnel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained('calendar_events')->nullOnDelete();
            $table->string('title');
            $table->string('status', 40)->default('open')->index();
            $table->string('candidate_type', 60)->default('associate');
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_start_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['owner_id', 'status', 'expected_start_at'], 'recruiting_opps_owner_status_start_idx');
        });

        Schema::create('contact_referrals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->ulid('referrer_contact_id')->nullable();
            $table->ulid('referrer_prospect_id')->nullable();
            $table->foreignId('referrer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ulid('referred_contact_id')->nullable();
            $table->ulid('referred_prospect_id')->nullable();
            $table->string('referral_type', 40)->default('prospect')->index();
            $table->string('status', 40)->default('new')->index();
            $table->string('source_label')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('referred_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('referrer_contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('referrer_prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->foreign('referred_contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('referred_prospect_id')->references('id')->on('prospects')->nullOnDelete();
            $table->index(['owner_id', 'status', 'referred_at'], 'contact_referrals_owner_status_referred_idx');
        });

        Schema::table('prospects', function (Blueprint $table): void {
            $table->ulid('contact_id')->nullable()->after('owner_id');
            $table->foreignId('sales_opportunity_id')->nullable()->after('contact_id')->constrained('sales_opportunities')->nullOnDelete();
            $table->foreignId('recruiting_opportunity_id')->nullable()->after('sales_opportunity_id')->constrained('recruiting_opportunities')->nullOnDelete();

            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->ulid('related_contact_id')->nullable()->after('related_prospect_id');
            $table->foreignId('related_sales_opportunity_id')->nullable()->after('related_contact_id')->constrained('sales_opportunities')->nullOnDelete();
            $table->foreignId('related_recruiting_opportunity_id')->nullable()->after('related_sales_opportunity_id')->constrained('recruiting_opportunities')->nullOnDelete();

            $table->foreign('related_contact_id')->references('id')->on('contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropForeign(['related_contact_id']);
            $table->dropConstrainedForeignId('related_sales_opportunity_id');
            $table->dropConstrainedForeignId('related_recruiting_opportunity_id');
            $table->dropColumn('related_contact_id');
        });

        Schema::table('prospects', function (Blueprint $table): void {
            $table->dropForeign(['contact_id']);
            $table->dropConstrainedForeignId('sales_opportunity_id');
            $table->dropConstrainedForeignId('recruiting_opportunity_id');
            $table->dropColumn('contact_id');
        });

        Schema::dropIfExists('contact_referrals');
        Schema::dropIfExists('recruiting_opportunities');
        Schema::dropIfExists('sales_opportunities');
        Schema::dropIfExists('contacts');
    }
};
