<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_buyer_matches', function (Blueprint $table) {
            $table->string('outreach_status', 30)->default('pending')->after('status');
            $table->text('buyer_notes')->nullable()->after('outreach_status');
            $table->timestamp('last_contacted_at')->nullable()->after('buyer_notes');
        });
    }

    public function down(): void
    {
        Schema::table('deal_buyer_matches', function (Blueprint $table) {
            $table->dropColumn(['outreach_status', 'buyer_notes', 'last_contacted_at']);
        });
    }
};
