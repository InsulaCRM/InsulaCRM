<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->decimal('listing_commission_pct', 5, 2)->nullable()->after('earnest_money');
            $table->decimal('buyer_commission_pct', 5, 2)->nullable()->after('listing_commission_pct');
            $table->decimal('total_commission', 12, 2)->nullable()->after('buyer_commission_pct');
            $table->string('mls_number', 30)->nullable()->after('total_commission');
            $table->date('listing_date')->nullable()->after('mls_number');
            $table->integer('days_on_market')->nullable()->after('listing_date');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn([
                'listing_commission_pct',
                'buyer_commission_pct',
                'total_commission',
                'mls_number',
                'listing_date',
                'days_on_market',
            ]);
        });
    }
};
