<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type', 50)->index(); // digest, pipeline_health, lead_snapshot, deal_analysis, stage_advice, score, dnc_risk, stale_deal_alert
            $table->string('model_type', 100)->nullable(); // App\Models\Lead, App\Models\Deal, etc.
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('prompt_summary')->nullable(); // brief context of what was asked
            $table->longText('result'); // the AI response
            $table->json('metadata')->nullable(); // extra structured data (score, risk_level, etc.)
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
