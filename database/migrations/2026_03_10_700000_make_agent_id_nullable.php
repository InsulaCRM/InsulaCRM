<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite columns are inherently nullable unless defined with NOT NULL in the original CREATE.
            // Since SQLite doesn't support ALTER COLUMN, and fresh SQLite schemas already have these nullable,
            // this is a no-op for SQLite (used in testing).
            return;
        }

        // Make agent_id nullable on leads (API-ingested leads have no agent yet)
        DB::statement('ALTER TABLE leads MODIFY COLUMN agent_id BIGINT UNSIGNED NULL');

        // Make agent_id nullable on deals (deals from API may not have an agent)
        DB::statement('ALTER TABLE deals MODIFY COLUMN agent_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE leads MODIFY COLUMN agent_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE deals MODIFY COLUMN agent_id BIGINT UNSIGNED NOT NULL');
    }
};
