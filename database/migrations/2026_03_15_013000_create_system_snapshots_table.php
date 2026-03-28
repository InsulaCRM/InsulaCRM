<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('version', 50);
            $table->string('backup_filename')->nullable();
            $table->string('snapshot_archive_path')->nullable();
            $table->string('snapshot_manifest_path')->nullable();
            $table->string('env_snapshot_path')->nullable();
            $table->string('status', 30)->default('ready');
            $table->text('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_snapshots');
    }
};
