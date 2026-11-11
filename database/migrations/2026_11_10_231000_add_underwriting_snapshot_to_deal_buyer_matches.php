<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_buyer_matches', function (Blueprint $table) {
            $table->json('underwriting_snapshot')->nullable()->after('match_score');
            $table->text('match_reason')->nullable()->after('underwriting_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('deal_buyer_matches', function (Blueprint $table) {
            $table->dropColumn(['underwriting_snapshot', 'match_reason']);
        });
    }
};
