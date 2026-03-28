<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('dashboard_widgets')->nullable()->after('email_mode');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->json('default_dashboard_widgets')->nullable()->after('notification_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dashboard_widgets');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('default_dashboard_widgets');
        });
    }
};
