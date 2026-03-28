<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leads - frequently filtered by tenant, agent, status, temperature
        Schema::table('leads', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('agent_id');
            $table->index('status');
            $table->index('temperature');
            $table->index('lead_source');
        });

        // Deals - filtered by tenant, stage, agent
        Schema::table('deals', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('agent_id');
            $table->index('stage');
        });

        // Activities - filtered by tenant, lead, deal, agent
        Schema::table('activities', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('lead_id');
            $table->index('deal_id');
            $table->index('agent_id');
        });

        // Tasks - filtered by tenant, lead, agent, due_date
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('lead_id');
            $table->index('agent_id');
            $table->index('due_date');
        });

        // Properties - filtered by tenant, lead
        Schema::table('properties', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('lead_id');
        });

        // Buyers - filtered by tenant
        Schema::table('buyers', function (Blueprint $table) {
            $table->index('tenant_id');
        });

        // Sequences - filtered by tenant
        Schema::table('sequences', function (Blueprint $table) {
            $table->index('tenant_id');
        });

        // Webhooks - filtered by tenant
        Schema::table('webhooks', function (Blueprint $table) {
            $table->index('tenant_id');
        });

        // Lead claims - filtered by tenant, lead, agent
        Schema::table('lead_claims', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('lead_id');
            $table->index('agent_id');
        });

        // Imports log - filtered by tenant
        Schema::table('imports_log', function (Blueprint $table) {
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['temperature']);
            $table->dropIndex(['lead_source']);
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['stage']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['lead_id']);
            $table->dropIndex(['deal_id']);
            $table->dropIndex(['agent_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['lead_id']);
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['lead_id']);
        });

        Schema::table('buyers', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });

        Schema::table('sequences', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });

        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });

        Schema::table('lead_claims', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['lead_id']);
            $table->dropIndex(['agent_id']);
        });

        Schema::table('imports_log', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });
    }
};
