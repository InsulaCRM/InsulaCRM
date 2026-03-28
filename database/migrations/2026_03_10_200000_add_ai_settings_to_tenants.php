<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(false)->after('api_enabled');
            $table->string('ai_provider', 20)->nullable()->after('ai_enabled');
            $table->text('ai_api_key')->nullable()->after('ai_provider');
            $table->string('ai_model', 100)->nullable()->after('ai_api_key');
            $table->string('ai_ollama_url', 255)->nullable()->after('ai_model');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['ai_enabled', 'ai_provider', 'ai_api_key', 'ai_model', 'ai_ollama_url']);
        });
    }
};
