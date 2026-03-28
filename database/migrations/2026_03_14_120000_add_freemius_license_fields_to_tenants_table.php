<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('license_key')->nullable()->after('support_token');
            $table->string('freemius_install_id')->nullable()->after('license_key');
            $table->text('freemius_install_token')->nullable()->after('freemius_install_id');
            $table->string('license_status')->default('unlicensed')->after('freemius_install_token');
            $table->timestamp('license_activated_at')->nullable()->after('license_status');
            $table->timestamp('license_expires_at')->nullable()->after('license_activated_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'license_key',
                'freemius_install_id',
                'freemius_install_token',
                'license_status',
                'license_activated_at',
                'license_expires_at',
            ]);
        });
    }
};
