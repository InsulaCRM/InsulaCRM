<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the plans table and remove the license column from tenants.
     */
    public function up(): void
    {
        Schema::dropIfExists('plans');

        if (Schema::hasColumn('tenants', 'license')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('license');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('max_users')->default(-1);
            $table->decimal('price', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('license')->nullable()->after('email');
        });
    }
};
