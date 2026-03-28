<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports_log', function (Blueprint $table) {
            $table->string('dedupe_strategy', 20)->default('skip')->after('status');
            $table->unsignedInteger('updated_rows')->default(0)->after('dedupe_strategy');
        });
    }

    public function down(): void
    {
        Schema::table('imports_log', function (Blueprint $table) {
            $table->dropColumn(['dedupe_strategy', 'updated_rows']);
        });
    }
};
