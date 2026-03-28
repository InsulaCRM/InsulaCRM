<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('deal_id')->nullable()->after('lead_id')->constrained('deals')->onDelete('cascade');
        });

        if (DB::getDriverName() !== 'sqlite') {
            // Make lead_id nullable (activities can belong to a deal instead)
            DB::statement('ALTER TABLE activities MODIFY COLUMN lead_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
            $table->dropColumn('deal_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE activities MODIFY COLUMN lead_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
