<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('deal_id')->constrained('deals')->onDelete('cascade');
            $table->string('item_key', 50);
            $table->string('label', 255);
            $table->string('status', 30)->default('pending');
            $table->date('deadline')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'deal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_checklists');
    }
};
