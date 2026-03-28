<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Upgrade leads table ───
        Schema::table('leads', function (Blueprint $table) {
            $table->integer('motivation_score')->default(0)->after('temperature');
            $table->boolean('do_not_contact')->default(false)->after('motivation_score');
            $table->string('timezone', 50)->nullable()->after('do_not_contact');
        });

        if (DB::getDriverName() !== 'sqlite') {
            // Expand lead_source enum to include list_import
            DB::statement("ALTER TABLE leads MODIFY COLUMN lead_source ENUM('cold_call','direct_mail','website','referral','driving_for_dollars','list_import','other') DEFAULT 'other'");

            // Expand status enum to full pipeline
            DB::statement("ALTER TABLE leads MODIFY COLUMN status ENUM('new','prospecting','contacting','engaging','contacted','negotiating','offer_presented','under_contract','dispositions','assigned','closing','closed','closed_won','closed_lost','dead') DEFAULT 'new'");
        }

        // ─── Upgrade properties table ───
        Schema::table('properties', function (Blueprint $table) {
            $table->integer('square_footage')->nullable()->after('bathrooms');
            $table->integer('year_built')->nullable()->after('square_footage');
            $table->string('lot_size', 50)->nullable()->after('year_built');
            $table->decimal('maximum_allowable_offer', 12, 2)->nullable()->after('our_offer');
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'distressed'])->nullable()->after('maximum_allowable_offer');
            $table->json('distress_markers')->nullable()->after('condition');
        });

        // ─── Upgrade activities table ───
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE activities MODIFY COLUMN type ENUM('call','sms','email','note','meeting','voicemail','direct_mail','stage_change') DEFAULT 'note'");
        }

        // ─── Upgrade deals table ───
        Schema::table('deals', function (Blueprint $table) {
            $table->decimal('earnest_money', 12, 2)->nullable()->after('assignment_fee');
            $table->integer('inspection_period_days')->nullable()->after('earnest_money');
            $table->date('due_diligence_end_date')->nullable()->after('contract_date');
        });

        // Expand deal stage enum to full pipeline
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE deals MODIFY COLUMN stage ENUM('new_lead','prospecting','contacting','engaging','contacted','offer_made','offer_presented','negotiating','under_contract','dispositions','assigned','closing','closed_won','closed_lost') DEFAULT 'new_lead'");
        }

        // ─── Upgrade tenants table ───
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('email');
            $table->string('timezone', 50)->default('America/New_York')->after('plan');
            $table->string('currency', 3)->default('USD')->after('timezone');
            $table->string('date_format', 20)->default('M d, Y')->after('currency');
            $table->string('distribution_method', 30)->default('round_robin')->after('date_format');
            $table->integer('claim_window_minutes')->default(3)->after('distribution_method');
            $table->integer('round_robin_index')->default(0)->after('claim_window_minutes');
            $table->boolean('timezone_restriction_enabled')->default(false)->after('round_robin_index');
        });

        // ─── Create DNC table ───
        Schema::create('do_not_contact_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();
            $table->index(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'email']);
        });

        // ─── Create lists table (for CSV import / list stacking) ───
        Schema::create('lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 50)->default('custom');
            $table->integer('record_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        // ─── Create list_leads pivot table ───
        Schema::create('list_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['list_id', 'lead_id']);
        });

        // ─── Create imports_log table ───
        Schema::create('imports_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('list_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // ─── Create lead_claims table ───
        Schema::create('lead_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('claimed')->default(false);
            $table->timestamps();
        });

        // ─── Create buyers table ───
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->decimal('max_purchase_price', 12, 2)->nullable();
            $table->json('preferred_property_types')->nullable();
            $table->json('preferred_zip_codes')->nullable();
            $table->json('preferred_states')->nullable();
            $table->json('asset_classes')->nullable();
            $table->integer('reliability_score')->default(100);
            $table->integer('total_deals_closed')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ─── Create deal_buyer_matches table ───
        Schema::create('deal_buyer_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            $table->integer('match_score')->default(0);
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['deal_id', 'buyer_id']);
        });

        // ─── Create sequences table (drip sequences) ───
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─── Create sequence_steps table ───
        Schema::create('sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(1);
            $table->integer('delay_days')->default(1);
            $table->enum('action_type', ['sms', 'email', 'call', 'voicemail', 'task', 'direct_mail']);
            $table->text('message_template')->nullable();
            $table->timestamps();
        });

        // ─── Create sequence_enrollments table ───
        Schema::create('sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->integer('current_step')->default(0);
            $table->timestamp('last_step_at')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            $table->unique(['sequence_id', 'lead_id']);
        });

        // ─── Create audit_log table ───
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'created_at']);
        });

        // ─── Create plugins table ───
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version', 20)->default('1.0.0');
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });

        // ─── Create marketplace_plugins table ───
        Schema::create('marketplace_plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('author');
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->string('version', 20)->default('1.0.0');
            $table->string('download_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        // ─── Create lead_source_costs table ───
        Schema::create('lead_source_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('lead_source');
            $table->decimal('monthly_budget', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'lead_source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_source_costs');
        Schema::dropIfExists('marketplace_plugins');
        Schema::dropIfExists('plugins');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('sequence_enrollments');
        Schema::dropIfExists('sequence_steps');
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('deal_buyer_matches');
        Schema::dropIfExists('buyers');
        Schema::dropIfExists('lead_claims');
        Schema::dropIfExists('imports_log');
        Schema::dropIfExists('list_leads');
        Schema::dropIfExists('lists');
        Schema::dropIfExists('do_not_contact_list');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'timezone', 'currency', 'date_format', 'distribution_method', 'claim_window_minutes', 'round_robin_index', 'timezone_restriction_enabled']);
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['earnest_money', 'inspection_period_days', 'due_diligence_end_date']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['square_footage', 'year_built', 'lot_size', 'maximum_allowable_offer', 'condition', 'distress_markers']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['motivation_score', 'do_not_contact', 'timezone']);
        });
    }
};
