<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50)->default('lead'); // lead, deal, buyer, property
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('field_type', 30); // text, textarea, number, date, select, checkbox
            $table->json('options')->nullable(); // for select fields: ["Option A", "Option B"]
            $table->boolean('required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'entity_type', 'slug']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::dropIfExists('custom_field_definitions');
    }
};
