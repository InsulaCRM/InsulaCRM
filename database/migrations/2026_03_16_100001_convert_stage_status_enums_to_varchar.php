<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert ENUM columns to VARCHAR so pipeline stages can be tenant-configurable.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE deals MODIFY COLUMN stage VARCHAR(50) NOT NULL DEFAULT 'prospecting'");
            DB::statement("ALTER TABLE leads MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'new'");
            DB::statement("ALTER TABLE properties MODIFY COLUMN property_type VARCHAR(50) NOT NULL DEFAULT 'single_family'");
            DB::statement("ALTER TABLE properties MODIFY COLUMN `condition` VARCHAR(50) NULL");
        } elseif (DB::getDriverName() === 'sqlite') {
            // SQLite ENUM creates CHECK constraints that block new values.
            // Use Laravel's change() which recreates the table without the CHECK.
            Schema::table('deals', function (Blueprint $table) {
                $table->string('stage', 50)->default('prospecting')->change();
            });
            Schema::table('leads', function (Blueprint $table) {
                $table->string('status', 50)->default('new')->change();
            });
            Schema::table('properties', function (Blueprint $table) {
                $table->string('property_type', 50)->default('single_family')->change();
                $table->string('condition', 50)->nullable()->change();
            });
        }

        // Add indexes for query performance (stage/status are frequently filtered)
        if (!$this->hasIndex('deals', 'deals_stage_index')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->index('stage');
            });
        }
        if (!$this->hasIndex('leads', 'leads_status_index')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        // Reverting to ENUM is not safe if new values have been inserted.
        // This migration is intentionally non-reversible.
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?", [$table, $indexName]);
            return count($indexes) > 0;
        }

        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
