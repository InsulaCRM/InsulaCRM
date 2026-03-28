<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('require_2fa')->default(false)->after('mail_settings');
            $table->string('sso_default_driver', 100)->nullable()->after('require_2fa');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['require_2fa', 'sso_default_driver']);
        });
    }
};
