<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('mao_strategy')->nullable()->after('maximum_allowable_offer');
            $table->decimal('buyer_max_all_in_percent', 5, 4)->nullable()->after('mao_strategy');
            $table->decimal('buyer_profit_target', 12, 2)->nullable()->after('buyer_max_all_in_percent');
            $table->decimal('transaction_costs', 12, 2)->nullable()->after('buyer_profit_target');
            $table->decimal('holding_costs', 12, 2)->nullable()->after('transaction_costs');
            $table->decimal('financing_costs', 12, 2)->nullable()->after('holding_costs');
            $table->decimal('risk_buffer', 12, 2)->nullable()->after('financing_costs');
            $table->decimal('assignment_fee_target', 12, 2)->nullable()->after('risk_buffer');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'mao_strategy',
                'buyer_max_all_in_percent',
                'buyer_profit_target',
                'transaction_costs',
                'holding_costs',
                'financing_costs',
                'risk_buffer',
                'assignment_fee_target',
            ]);
        });
    }
};
