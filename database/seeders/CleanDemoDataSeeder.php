<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanDemoDataSeeder extends Seeder
{
    /**
     * Remove all demo data. Keeps roles intact.
     */
    public function run(): void
    {
        $this->command->info('Cleaning demo data...');

        // New tables
        DB::table('lead_source_costs')->truncate();
        DB::table('do_not_contact_list')->truncate();
        DB::table('audit_log')->truncate();
        DB::table('sequence_enrollments')->truncate();
        DB::table('sequence_steps')->truncate();
        DB::table('sequences')->truncate();
        DB::table('lead_claims')->truncate();
        DB::table('imports_log')->truncate();
        DB::table('list_leads')->truncate();
        DB::table('lists')->truncate();
        DB::table('deal_buyer_matches')->truncate();
        DB::table('buyers')->truncate();

        // Existing tables
        DB::table('deal_documents')->truncate();
        DB::table('deals')->truncate();
        DB::table('tasks')->truncate();
        DB::table('activities')->truncate();
        DB::table('properties')->truncate();
        DB::table('leads')->truncate();

        // Remove all demo users and tenants
        DB::table('plugins')->truncate();
        DB::table('users')->truncate();
        DB::table('tenants')->truncate();

        $this->command->info('Demo data cleaned successfully. Run the installer or register to create a new tenant.');
    }
}
