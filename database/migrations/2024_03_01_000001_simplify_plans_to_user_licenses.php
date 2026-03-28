<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Restructure plans table: drop feature-gating columns, add max_users
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_agents', 'max_leads', 'price_monthly']);
            $table->integer('max_users')->default(5)->after('name');
            $table->decimal('price', 8, 2)->default(0)->after('max_users');
        });

        // Clear old plans and insert user-count-based license tiers
        DB::table('plans')->truncate();
        DB::table('plans')->insert([
            ['name' => '5 Users', 'max_users' => 5, 'price' => 49.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '10 Users', 'max_users' => 10, 'price' => 89.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '25 Users', 'max_users' => 25, 'price' => 149.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '50 Users', 'max_users' => 50, 'price' => 249.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Unlimited', 'max_users' => -1, 'price' => 399.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Rename 'plan' to 'license' on tenants, update existing values
        Schema::table('tenants', function (Blueprint $table) {
            $table->renameColumn('plan', 'license');
        });

        // Map old plan names to new license tiers
        DB::table('tenants')->where('license', 'starter')->update(['license' => '5 Users']);
        DB::table('tenants')->where('license', 'pro')->update(['license' => '10 Users']);
        DB::table('tenants')->where('license', 'agency')->update(['license' => 'Unlimited']);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->renameColumn('license', 'plan');
        });

        DB::table('tenants')->where('plan', '5 Users')->update(['plan' => 'starter']);
        DB::table('tenants')->where('plan', '10 Users')->update(['plan' => 'pro']);
        DB::table('tenants')->where('plan', 'Unlimited')->update(['plan' => 'agency']);

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_users', 'price']);
            $table->integer('max_agents')->default(-1);
            $table->integer('max_leads')->default(-1);
            $table->decimal('price_monthly', 8, 2)->default(0);
        });
    }
};
