<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->boolean('pof_verified')->default(false)->after('notes');
            $table->string('pof_document_path')->nullable()->after('pof_verified');
            $table->decimal('pof_amount', 14, 2)->nullable()->after('pof_document_path');
            $table->timestamp('pof_verified_at')->nullable()->after('pof_amount');
            $table->integer('buyer_score')->default(0)->after('pof_verified_at'); // 0-100
            $table->integer('total_purchases')->default(0)->after('buyer_score');
            $table->decimal('avg_close_days', 8, 1)->default(0)->after('total_purchases');
            $table->timestamp('last_purchase_at')->nullable()->after('avg_close_days');
        });

        Schema::create('buyer_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('buyer_id')->index();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->string('property_address')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->date('close_date')->nullable();
            $table->integer('days_to_close')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'buyer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_transactions');
        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn([
                'pof_verified', 'pof_document_path', 'pof_amount', 'pof_verified_at',
                'buyer_score', 'total_purchases', 'avg_close_days', 'last_purchase_at',
            ]);
        });
    }
};
