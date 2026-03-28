<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('support_last_checked_at')->nullable()->after('support_token');
            $table->string('last_notified_version', 20)->nullable()->after('support_last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['support_last_checked_at', 'last_notified_version']);
        });
    }
};
