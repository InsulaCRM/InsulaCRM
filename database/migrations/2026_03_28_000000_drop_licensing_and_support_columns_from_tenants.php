<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $columns = [
                'license_key',
                'freemius_install_id',
                'freemius_install_token',
                'license_status',
                'license_activated_at',
                'license_expires_at',
                'support_api_url',
                'support_token',
                'support_last_checked_at',
                'last_notified_version',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('license_key')->nullable();
            $table->string('freemius_install_id')->nullable();
            $table->text('freemius_install_token')->nullable();
            $table->string('license_status')->nullable();
            $table->timestamp('license_activated_at')->nullable();
            $table->timestamp('license_expires_at')->nullable();
            $table->string('support_api_url')->nullable();
            $table->string('support_token')->nullable();
            $table->timestamp('support_last_checked_at')->nullable();
            $table->string('last_notified_version')->nullable();
        });
    }
};
