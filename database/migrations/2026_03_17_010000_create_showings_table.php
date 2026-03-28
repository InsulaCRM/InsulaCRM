<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->date('showing_date');
            $table->time('showing_time');
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->string('status', 30)->default('scheduled');
            $table->text('feedback')->nullable();
            $table->string('outcome', 30)->nullable();
            $table->string('listing_agent_name', 255)->nullable();
            $table->string('listing_agent_phone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'showing_date']);
            $table->index(['tenant_id', 'agent_id', 'showing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showings');
    }
};
