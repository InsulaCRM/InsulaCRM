<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Re-encrypt any existing plaintext AI API keys.
     *
     * The Tenant model now uses the 'encrypted' cast on ai_api_key,
     * so any previously stored plaintext values must be encrypted.
     */
    public function up(): void
    {
        $tenants = DB::table('tenants')->whereNotNull('ai_api_key')->where('ai_api_key', '!=', '')->get();

        foreach ($tenants as $tenant) {
            $value = $tenant->ai_api_key;

            // Skip if already encrypted (encrypted values are much longer than raw keys)
            try {
                decrypt($value);
                continue; // Already encrypted
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Not encrypted yet — encrypt it
            }

            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['ai_api_key' => encrypt($value)]);
        }
    }

    public function down(): void
    {
        // Cannot safely reverse encryption without risking data loss.
    }
};
