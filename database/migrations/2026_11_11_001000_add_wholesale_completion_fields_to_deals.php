<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->string('completion_status')->nullable()->after('closing_date');
            $table->timestamp('assignment_completed_at')->nullable()->after('completion_status');
            $table->timestamp('closed_won_at')->nullable()->after('assignment_completed_at');
            $table->decimal('final_assignment_fee', 12, 2)->nullable()->after('closed_won_at');
            $table->decimal('final_closing_amount', 12, 2)->nullable()->after('final_assignment_fee');
            $table->string('closing_result')->nullable()->after('final_closing_amount');
            $table->text('closing_result_notes')->nullable()->after('closing_result');
            $table->foreignId('completion_recorded_by')->nullable()->after('closing_result_notes')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['completion_recorded_by']);
            $table->dropColumn([
                'completion_status',
                'assignment_completed_at',
                'closed_won_at',
                'final_assignment_fee',
                'final_closing_amount',
                'closing_result',
                'closing_result_notes',
                'completion_recorded_by',
            ]);
        });
    }
};
