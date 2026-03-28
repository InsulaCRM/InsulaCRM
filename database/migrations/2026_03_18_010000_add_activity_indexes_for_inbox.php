<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->index(['tenant_id', 'logged_at'], 'activities_tenant_logged_at_index');
            $table->index(['tenant_id', 'agent_id'], 'activities_tenant_agent_index');
            $table->index(['tenant_id', 'type'], 'activities_tenant_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_tenant_logged_at_index');
            $table->dropIndex('activities_tenant_agent_index');
            $table->dropIndex('activities_tenant_type_index');
        });
    }
};
