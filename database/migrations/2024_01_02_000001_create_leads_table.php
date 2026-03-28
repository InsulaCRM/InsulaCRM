<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('lead_source', 100)->default('other');
            $table->enum('status', ['new', 'prospecting', 'contacting', 'engaging', 'contacted', 'negotiating', 'offer_presented', 'under_contract', 'dispositions', 'assigned', 'closing', 'closed', 'closed_won', 'closed_lost', 'dead'])->default('new');
            $table->enum('temperature', ['hot', 'warm', 'cold'])->default('cold');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
