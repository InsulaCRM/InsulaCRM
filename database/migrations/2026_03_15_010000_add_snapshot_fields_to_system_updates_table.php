<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_updates', function (Blueprint $table) {
            $table->string('snapshot_archive_path')->nullable()->after('backup_filename');
            $table->string('snapshot_manifest_path')->nullable()->after('snapshot_archive_path');
            $table->timestamp('snapshot_created_at')->nullable()->after('snapshot_manifest_path');
            $table->timestamp('restored_at')->nullable()->after('snapshot_created_at');
            $table->text('restore_summary')->nullable()->after('restored_at');
            $table->text('restore_error_message')->nullable()->after('restore_summary');
        });
    }

    public function down(): void
    {
        Schema::table('system_updates', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_archive_path',
                'snapshot_manifest_path',
                'snapshot_created_at',
                'restored_at',
                'restore_summary',
                'restore_error_message',
            ]);
        });
    }
};
