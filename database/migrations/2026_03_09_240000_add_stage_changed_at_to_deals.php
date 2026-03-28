<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->timestamp('stage_changed_at')->nullable()->after('stage');
        });

        // Backfill existing deals with created_at as a reasonable default
        DB::table('deals')->whereNull('stage_changed_at')->update([
            'stage_changed_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('stage_changed_at');
        });
    }
};
