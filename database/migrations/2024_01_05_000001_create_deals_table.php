<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->enum('stage', ['new_lead', 'prospecting', 'contacting', 'engaging', 'contacted', 'offer_made', 'offer_presented', 'negotiating', 'under_contract', 'dispositions', 'assigned', 'closing', 'closed_won', 'closed_lost'])->default('new_lead');
            $table->decimal('contract_price', 12, 2)->nullable();
            $table->decimal('assignment_fee', 12, 2)->nullable();
            $table->date('contract_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('size');
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_documents');
        Schema::dropIfExists('deals');
    }
};
