<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_signature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('generated_document_id')->nullable()->constrained('generated_documents')->nullOnDelete();

            $table->string('document_type')->default('wholesale_contract');
            $table->string('title');
            $table->string('status')->default('draft');
            $table->string('opensign_object_id')->nullable()->unique();
            $table->json('signers')->nullable();
            $table->json('signing_urls')->nullable();

            $table->string('source_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('archive_directory')->nullable();

            $table->string('external_file_url')->nullable();
            $table->string('external_certificate_url')->nullable();
            $table->text('declined_reason')->nullable();
            $table->json('last_event_payload')->nullable();
            $table->string('last_event')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'deal_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_signature_requests');
    }
};
