<?php

namespace Tests\Feature;

use App\Models\GeneratedDocument;
use App\Services\OpenSignService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpenSignServiceTest extends TestCase
{
    public function test_signature_request_is_sent_and_recorded(): void
    {
        $this->actingAsAdmin();

        Storage::fake('local');
        Storage::disk('local')->put('contracts/test.pdf', '%PDF-test');

        $deal = $this->createDeal([
            'stage' => 'dispositions',
            'contract_gate_status' => 'approved',
        ]);

        $document = GeneratedDocument::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'name' => 'Purchase Agreement',
            'content' => 'test',
            'pdf_path' => 'contracts/test.pdf',
        ]);

        config([
            'filesystems.default' => 'local',
            'services.opensign.api_token' => 'sandbox-token',
            'services.opensign.base_url' => 'https://sandbox.opensignlabs.com/api/v1.2',
        ]);

        Http::fake([
            'https://sandbox.opensignlabs.com/api/v1.2/createdocument' => Http::response([
                'objectId' => 'opensign-test-123',
                'signurl' => ['https://sandbox.opensignlabs.com/sign/test'],
            ], 200),
        ]);

        $record = app(OpenSignService::class)->createSignatureRequest(
            $deal,
            $document,
            [
                [
                    'name' => 'Seller',
                    'email' => 'seller@example.com',
                    'role' => 'seller',
                ],
            ],
            [[
                'type' => 'signature',
                'page' => 1,
                'x' => 100,
                'y' => 100,
            ]]
        );

        $this->assertSame('sent', $record->status);
        $this->assertSame('opensign-test-123', $record->opensign_object_id);
        $this->assertSame(
            ['https://sandbox.opensignlabs.com/sign/test'],
            $record->signing_urls
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.opensignlabs.com/api/v1.2/createdocument'
                && $request->hasHeader('x-api-token', 'sandbox-token')
                && $request['send_email'] === true;
        });
    }

    public function test_tenant_default_template_is_used_for_assignment_documents(): void
    {
        $this->actingAsAdmin([
            'custom_options' => [
                'opensign' => [
                    'api_token' => encrypt('tenant-token'),
                    'base_url' => 'https://sandbox.opensignlabs.com/api/v1.2',
                    'template_ids' => [
                        'assignment' => 'tpl-assignment',
                    ],
                ],
            ],
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('contracts/assignment.pdf', '%PDF-test');

        $deal = $this->createDeal([
            'stage' => 'dispositions',
            'contract_gate_status' => 'approved',
        ]);

        $document = GeneratedDocument::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'name' => 'Assignment Agreement',
            'content' => 'test',
            'pdf_path' => 'contracts/assignment.pdf',
        ]);

        Http::fake([
            'https://sandbox.opensignlabs.com/api/v1.2/createdocument/tpl-assignment' => Http::response([
                'objectId' => 'opensign-template-123',
                'signurl' => ['https://sandbox.opensignlabs.com/sign/template'],
            ], 200),
        ]);

        app(OpenSignService::class)->createSignatureRequest(
            $deal,
            $document,
            [[
                'name' => 'Seller',
                'email' => 'seller@example.com',
                'role' => 'seller',
            ]],
            [],
            ['document_type' => 'assignment']
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.opensignlabs.com/api/v1.2/createdocument/tpl-assignment'
                && $request->hasHeader('x-api-token', 'tenant-token')
                && !isset($request['file']);
        });
    }

    public function test_completed_webhook_archives_signed_document_and_certificate(): void
    {
        $this->actingAsAdmin();

        Storage::fake('local');

        $deal = $this->createDeal([
            'stage' => 'dispositions',
            'contract_gate_status' => 'approved',
        ]);

        $request = \App\Models\DealSignatureRequest::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'document_type' => 'wholesale_contract',
            'title' => 'Purchase Agreement',
            'status' => 'sent',
            'opensign_object_id' => 'opensign-complete-123',
            'archive_directory' => 'deals/' . $deal->id . '/executed',
        ]);

        Http::fake([
            'https://signed.example.com/executed.pdf' => Http::response('%PDF-signed', 200, ['Content-Type' => 'application/pdf']),
            'https://signed.example.com/certificate.pdf' => Http::response('%PDF-cert', 200, ['Content-Type' => 'application/pdf']),
        ]);

        config([
            'services.opensign.webhook_secret' => 'test-secret',
            'filesystems.default' => 'local',
        ]);

        $record = app(OpenSignService::class)->handleWebhook([
            'objectId' => 'opensign-complete-123',
            'event' => 'completed',
            'file' => 'https://signed.example.com/executed.pdf',
            'certificate' => 'https://signed.example.com/certificate.pdf',
        ]);

        $this->assertSame('completed', $record->status);
        $this->assertNotNull($record->signed_pdf_path);
        $this->assertNotNull($record->certificate_path);

        Storage::disk('local')->assertExists($record->signed_pdf_path);
        Storage::disk('local')->assertExists($record->certificate_path);

        $this->assertDatabaseHas('deal_documents', [
            'deal_id' => $deal->id,
            'filename' => 'Purchase-Agreement-executed.pdf',
        ]);

        $this->assertDatabaseHas('deal_documents', [
            'deal_id' => $deal->id,
            'filename' => 'Purchase-Agreement-completion-certificate.pdf',
        ]);
    }


    public function test_completed_webhook_retry_does_not_duplicate_a_document_when_the_first_archive_was_partial(): void
    {
        $this->actingAsAdmin();

        Storage::fake('local');

        $deal = $this->createDeal([
            'stage' => 'dispositions',
            'contract_gate_status' => 'approved',
        ]);

        \App\Models\DealSignatureRequest::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'document_type' => 'wholesale_contract',
            'title' => 'Purchase Agreement',
            'status' => 'sent',
            'opensign_object_id' => 'opensign-partial-123',
            'archive_directory' => 'deals/' . $deal->id . '/executed',
        ]);

        Http::fake([
            'https://signed.example.com/executed.pdf' => Http::response('%PDF-signed', 200),
            'https://signed.example.com/certificate.pdf' => Http::sequence()
                ->pushStatus(500)
                ->push('%PDF-cert', 200),
        ]);

        $payload = [
            'objectId' => 'opensign-partial-123',
            'event' => 'completed',
            'file' => 'https://signed.example.com/executed.pdf',
            'certificate' => 'https://signed.example.com/certificate.pdf',
        ];

        app(OpenSignService::class)->handleWebhook($payload);
        app(OpenSignService::class)->handleWebhook($payload);

        $this->assertDatabaseCount('deal_documents', 2);
        $this->assertDatabaseHas('deal_documents', [
            'deal_id' => $deal->id,
            'filename' => 'Purchase-Agreement-executed.pdf',
        ]);
        $this->assertDatabaseHas('deal_documents', [
            'deal_id' => $deal->id,
            'filename' => 'Purchase-Agreement-completion-certificate.pdf',
        ]);
    }

    public function test_completed_webhook_does_not_archive_the_same_request_twice(): void
    {
        $this->actingAsAdmin();

        Storage::fake('local');

        $deal = $this->createDeal([
            'stage' => 'dispositions',
            'contract_gate_status' => 'approved',
        ]);

        $request = \App\Models\DealSignatureRequest::create([
            'tenant_id' => $this->tenant->id,
            'deal_id' => $deal->id,
            'document_type' => 'wholesale_contract',
            'title' => 'Purchase Agreement',
            'status' => 'sent',
            'opensign_object_id' => 'opensign-idempotent-123',
            'archive_directory' => 'deals/' . $deal->id . '/executed',
        ]);

        Http::fake([
            'https://signed.example.com/executed.pdf' => Http::response('%PDF-signed', 200),
            'https://signed.example.com/certificate.pdf' => Http::response('%PDF-cert', 200),
        ]);

        $payload = [
            'objectId' => 'opensign-idempotent-123',
            'event' => 'completed',
            'file' => 'https://signed.example.com/executed.pdf',
            'certificate' => 'https://signed.example.com/certificate.pdf',
        ];

        app(OpenSignService::class)->handleWebhook($payload);
        app(OpenSignService::class)->handleWebhook($payload);

        $this->assertDatabaseCount('deal_documents', 2);
    }
}
