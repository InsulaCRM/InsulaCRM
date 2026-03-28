<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('deal_id')->constrained('deals')->onDelete('cascade');
            $table->string('buyer_name', 255);
            $table->string('buyer_agent_name', 255)->nullable();
            $table->string('buyer_agent_phone', 50)->nullable();
            $table->string('buyer_agent_email', 255)->nullable();
            $table->decimal('offer_price', 12, 2);
            $table->decimal('earnest_money', 12, 2)->nullable();
            $table->string('financing_type', 50)->nullable();
            $table->json('contingencies')->nullable();
            $table->dateTime('expiration_date')->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('counter_price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'deal_id']);
            $table->index(['tenant_id', 'deal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_offers');
    }
};
