<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_signature_requests', function (Blueprint $table) {
            $table->string('opensign_template_id')->nullable()->after('opensign_object_id');
        });
    }

    public function down(): void
    {
        Schema::table('deal_signature_requests', function (Blueprint $table) {
            $table->dropColumn('opensign_template_id');
        });
    }
};
