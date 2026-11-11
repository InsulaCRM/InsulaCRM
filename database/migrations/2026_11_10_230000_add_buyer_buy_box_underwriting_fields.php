<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->decimal('max_all_in_percent', 5, 4)->nullable()->after('max_purchase_price');
            $table->decimal('target_profit', 12, 2)->nullable()->after('max_all_in_percent');
            $table->json('preferred_strategies')->nullable()->after('preferred_property_types');
            $table->json('preferred_conditions')->nullable()->after('preferred_strategies');
            $table->decimal('transaction_cost_percent', 5, 4)->nullable()->after('preferred_conditions');
            $table->decimal('holding_cost_percent', 5, 4)->nullable()->after('transaction_cost_percent');
            $table->decimal('financing_cost_percent', 5, 4)->nullable()->after('holding_cost_percent');
            $table->decimal('risk_buffer_percent', 5, 4)->nullable()->after('financing_cost_percent');
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn([
                'max_all_in_percent',
                'target_profit',
                'preferred_strategies',
                'preferred_conditions',
                'transaction_cost_percent',
                'holding_cost_percent',
                'financing_cost_percent',
                'risk_buffer_percent',
            ]);
        });
    }
};
