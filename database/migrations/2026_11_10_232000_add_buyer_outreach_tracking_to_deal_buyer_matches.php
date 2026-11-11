<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_buyer_matches', function (Blueprint $table) {
            $table->string('outreach_channel')->nullable()->after('outreach_status');
            $table->string('response_intent')->nullable()->after('outreach_channel');
            $table->string('commitment_status')->nullable()->after('response_intent');
            $table->timestamp('outreach_sent_at')->nullable()->after('commitment_status');
            $table->timestamp('response_received_at')->nullable()->after('outreach_sent_at');
            $table->text('response_body')->nullable()->after('response_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('deal_buyer_matches', function (Blueprint $table) {
            $table->dropColumn([
                'outreach_channel',
                'response_intent',
                'commitment_status',
                'outreach_sent_at',
                'response_received_at',
                'response_body',
            ]);
        });
    }
};
