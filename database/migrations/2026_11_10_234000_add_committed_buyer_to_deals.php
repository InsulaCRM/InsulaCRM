<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('committed_buyer_id')->nullable()->after('buyer_legal_name')->constrained('buyers')->nullOnDelete();
            $table->timestamp('buyer_committed_at')->nullable()->after('committed_buyer_id');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['committed_buyer_id']);
            $table->dropColumn(['committed_buyer_id', 'buyer_committed_at']);
        });
    }
};
