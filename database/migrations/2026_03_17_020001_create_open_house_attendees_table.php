<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_house_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('open_house_id')->constrained('open_houses')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('interested')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'open_house_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_house_attendees');
    }
};
