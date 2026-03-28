<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('version_from', 50);
            $table->string('version_to', 50);
            $table->string('package_name');
            $table->string('package_sha256', 64);
            $table->string('stage_path');
            $table->string('env_snapshot_path')->nullable();
            $table->string('backup_filename')->nullable();
            $table->string('status', 30)->default('prepared');
            $table->json('warnings')->nullable();
            $table->text('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_updates');
    }
};
