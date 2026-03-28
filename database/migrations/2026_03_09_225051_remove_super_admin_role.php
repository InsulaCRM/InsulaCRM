<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove the super_admin role and reassign any users with that role to admin.
     */
    public function up(): void
    {
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();

        if ($superAdminRole && $adminRole) {
            // Reassign any super_admin users to admin
            DB::table('users')
                ->where('role_id', $superAdminRole->id)
                ->update(['role_id' => $adminRole->id]);

            // Delete the super_admin role
            DB::table('roles')->where('id', $superAdminRole->id)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->insert([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
