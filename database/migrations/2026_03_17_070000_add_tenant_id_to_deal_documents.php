<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        // Backfill tenant_id from parent deal
        DB::statement('UPDATE deal_documents SET tenant_id = (SELECT tenant_id FROM deals WHERE deals.id = deal_documents.deal_id)');

        Schema::table('deal_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('deal_documents', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
