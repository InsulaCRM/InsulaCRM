<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('buyer_portal_enabled')->default(false)->after('ai_enabled');
            $table->string('buyer_portal_headline')->nullable()->after('buyer_portal_enabled');
            $table->text('buyer_portal_description')->nullable()->after('buyer_portal_headline');
            $table->json('buyer_portal_config')->nullable()->after('buyer_portal_description');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_portal_enabled', 'buyer_portal_headline',
                'buyer_portal_description', 'buyer_portal_config',
            ]);
        });
    }
};
