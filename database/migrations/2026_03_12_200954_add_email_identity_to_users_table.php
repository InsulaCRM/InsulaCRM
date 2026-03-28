<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_from_name', 100)->nullable()->after('email');
            $table->string('email_reply_to', 255)->nullable()->after('email_from_name');
            $table->string('email_mode', 10)->default('shared')->after('email_reply_to');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_from_name', 'email_reply_to', 'email_mode']);
        });
    }
};
