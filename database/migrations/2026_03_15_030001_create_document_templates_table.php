<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('type', 50)->index(); // loi, purchase_agreement, assignment_contract, addendum, other
            $table->longText('content'); // HTML template with merge fields
            $table->json('merge_fields')->nullable(); // available merge fields for this template
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('deal_id')->index();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->longText('content'); // rendered HTML
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'deal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('document_templates');
    }
};
