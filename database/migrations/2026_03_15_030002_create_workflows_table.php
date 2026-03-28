<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type', 80); // lead.created, lead.status_changed, deal.stage_changed, activity.logged, task.overdue, lead.score_above, manual
            $table->json('trigger_config')->nullable(); // e.g. {"status":"hot"}, {"stage":"under_contract"}
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['trigger_type', 'is_active']);
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->index();
            $table->integer('position')->default(0);
            $table->string('type', 30); // action, condition, delay
            $table->string('action_type', 50)->nullable(); // send_email, send_sms, create_task, update_field, assign_agent, add_tag, notify_user, webhook
            $table->json('config')->nullable(); // action-specific configuration
            $table->integer('delay_minutes')->default(0); // for delay steps
            // Branching for conditions
            $table->string('condition_field')->nullable();
            $table->string('condition_operator', 20)->nullable(); // equals, not_equals, contains, greater_than, less_than, is_empty, is_not_empty
            $table->string('condition_value')->nullable();
            $table->unsignedBigInteger('next_step_id')->nullable(); // default next / "yes" branch
            $table->unsignedBigInteger('alt_step_id')->nullable(); // "no" branch for conditions
            $table->timestamps();

            $table->index(['workflow_id', 'position']);
        });

        Schema::create('workflow_run_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id')->index();
            $table->unsignedBigInteger('step_id')->nullable();
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('status', 20); // started, completed, failed, skipped, waiting
            $table->text('result')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // for delayed steps
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'status']);
            $table->index(['model_type', 'model_id']);
            $table->index(['status', 'scheduled_at']); // for processing delayed steps
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_run_logs');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');
    }
};
