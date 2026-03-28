<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 30)->default('scheduled');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('attendee_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'event_date']);
            $table->index(['tenant_id', 'agent_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_houses');
    }
};
