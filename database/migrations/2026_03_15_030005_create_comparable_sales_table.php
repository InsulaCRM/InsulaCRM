<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparable_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->string('address');
            $table->decimal('sale_price', 12, 2);
            $table->date('sale_date');
            $table->integer('sqft')->nullable();
            $table->tinyInteger('beds')->nullable();
            $table->decimal('baths', 3, 1)->nullable();
            $table->decimal('lot_size', 10, 2)->nullable();
            $table->integer('year_built')->nullable();
            $table->decimal('distance_miles', 6, 2)->nullable();
            $table->string('condition', 30)->nullable(); // excellent, good, fair, poor
            $table->json('adjustments')->nullable(); // {"sqft": -5000, "condition": 10000, "garage": -3000}
            $table->decimal('adjusted_price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparable_sales');
    }
};
