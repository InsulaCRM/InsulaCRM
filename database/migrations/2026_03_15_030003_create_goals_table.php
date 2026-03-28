<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable(); // null = team-wide goal
            $table->string('metric', 50); // deals_closed, revenue_earned, leads_generated, activities_logged, calls_made, offers_sent, assignments_closed
            $table->decimal('target_value', 14, 2);
            $table->string('period', 20); // weekly, monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
