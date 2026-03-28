<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('entity_type', 30);
            $table->string('name', 100);
            $table->json('filters');
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'entity_type']);
            $table->index(['tenant_id', 'user_id', 'entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_views');
    }
};
