<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->string('contract_gate_status')->nullable()->after('extension_termination_review_status');
            $table->text('contract_gate_reason')->nullable()->after('contract_gate_status');
            $table->json('contract_gate_blockers')->nullable()->after('contract_gate_reason');
            $table->timestamp('contract_gate_checked_at')->nullable()->after('contract_gate_blockers');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn([
                'contract_gate_status',
                'contract_gate_reason',
                'contract_gate_blockers',
                'contract_gate_checked_at',
            ]);
        });
    }
};
