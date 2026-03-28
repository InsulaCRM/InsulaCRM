<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('name');
            $table->string('type', 50)->index(); // direct_mail, ppc, cold_call, bandit_sign, seo, social, email, ringless_voicemail, other
            $table->string('status', 20)->default('draft'); // draft, active, paused, completed
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('actual_spend', 12, 2)->default(0);
            $table->integer('target_count')->default(0); // mail pieces, ad impressions, calls, etc.
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // extra structured data
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('lead_source');
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['campaign_id']);
            $table->dropColumn('campaign_id');
        });
        Schema::dropIfExists('campaigns');
    }
};
