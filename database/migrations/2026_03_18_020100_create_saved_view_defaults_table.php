<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_view_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('entity_type', 30);
            $table->foreignId('saved_view_id')->constrained('saved_views')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_view_defaults');
    }
};
