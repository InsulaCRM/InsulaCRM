<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->string('property_state', 2)->nullable()->after('lead_id');
            $table->string('property_county')->nullable()->after('property_state');
            $table->string('buyer_type', 20)->nullable()->after('property_county');
            $table->string('buyer_legal_name')->nullable()->after('buyer_type');
            $table->string('transaction_structure', 30)->nullable()->after('buyer_legal_name');
            $table->string('attorney_approved_template_id')->nullable()->after('transaction_structure');
            $table->string('executed_contract_status', 30)->nullable()->after('attorney_approved_template_id');
            $table->boolean('assignment_rights_confirmed')->nullable()->after('executed_contract_status');
            $table->boolean('emd_required')->nullable()->after('assignment_rights_confirmed');
            $table->string('emd_source', 30)->nullable()->after('emd_required');
            $table->boolean('buyer_covers_emd')->nullable()->after('emd_source');
            $table->boolean('emd_funding_confirmed')->nullable()->after('buyer_covers_emd');
            $table->string('emd_exception_reason')->nullable()->after('emd_funding_confirmed');
            $table->string('closing_provider_type', 30)->nullable()->after('emd_exception_reason');
            $table->string('buyer_preferred_closing_provider')->nullable()->after('closing_provider_type');
            $table->string('seller_preferred_closing_provider')->nullable()->after('buyer_preferred_closing_provider');
            $table->string('confirmed_closing_provider')->nullable()->after('seller_preferred_closing_provider');
            $table->string('contract_review_status', 30)->nullable()->after('confirmed_closing_provider');
            $table->string('no_buyer_status', 30)->nullable()->after('contract_review_status');
            $table->string('extension_termination_review_status', 30)->nullable()->after('no_buyer_status');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn([
                'property_state',
                'property_county',
                'buyer_type',
                'buyer_legal_name',
                'transaction_structure',
                'attorney_approved_template_id',
                'executed_contract_status',
                'assignment_rights_confirmed',
                'emd_required',
                'emd_source',
                'buyer_covers_emd',
                'emd_funding_confirmed',
                'emd_exception_reason',
                'closing_provider_type',
                'buyer_preferred_closing_provider',
                'seller_preferred_closing_provider',
                'confirmed_closing_provider',
                'contract_review_status',
                'no_buyer_status',
                'extension_termination_review_status',
            ]);
        });
    }
};
