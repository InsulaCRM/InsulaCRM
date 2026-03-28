<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('list_price', 12, 2)->nullable()->after('asking_price');
            $table->string('listing_status', 30)->nullable()->after('list_price');
            $table->date('listed_at')->nullable()->after('listing_status');
            $table->date('sold_at')->nullable()->after('listed_at');
            $table->decimal('sold_price', 12, 2)->nullable()->after('sold_at');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'list_price',
                'listing_status',
                'listed_at',
                'sold_at',
                'sold_price',
            ]);
        });
    }
};
