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
            $table->foreignId('property_id')
                ->nullable()
                ->after('lead_id')
                ->constrained('properties')
                ->nullOnDelete();

            $table->index(['tenant_id', 'property_id']);
        });

        // Backfill existing deals through their seller/lead relationship.
        DB::table('deals')
            ->whereNull('property_id')
            ->orderBy('id')
            ->chunkById(500, function ($deals): void {
                foreach ($deals as $deal) {
                    $propertyId = DB::table('properties')
                        ->where('tenant_id', $deal->tenant_id)
                        ->where('lead_id', $deal->lead_id)
                        ->value('id');

                    if ($propertyId) {
                        DB::table('deals')
                            ->where('id', $deal->id)
                            ->update(['property_id' => $propertyId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropIndex(['tenant_id', 'property_id']);
            $table->dropColumn('property_id');
        });
    }
};
