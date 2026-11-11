<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\DealDocument;
use App\Models\DealSignatureRequest;
use App\Models\GeneratedDocument;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OpenSignService
{
    public function configured(?int $tenantId = null): bool
    {
        if ($tenantId) {
            $settings = $this->settings($tenantId);
            if (!empty($settings['api_token'])) {
                return true;
            }
        }

        return (bool) config('services.opensign.api_token');
    }

    public function settings(?int $tenantId = null): array
    {
        $settings = [];

        if ($tenantId) {
            $tenant = \App\Models\Tenant::withoutGlobalScopes()->find($tenantId);
            $settings = $tenant?->custom_options['opensign'] ?? [];

            foreach (['api_token', 'webhook_secret'] as $key) {
                if (!empty($settings[$key])) {
                    try {
                        $settings[$key] = decrypt($settings[$key]);
                    } catch (\Throwable $e) {
                        // Backward-compatible with legacy plain-text tenant settings.
                    }
                }
            }
        }

        return array_merge([
            'base_url' => config('services.opensign.base_url'),
            'api_token' => config('services.opensign.api_token'),
            'webhook_secret' => config('services.opensign.webhook_secret'),
            'sender_name' => config('services.opensign.sender_name', config('app.name')),
            'sender_email' => config('services.opensign.sender_email'),
        ], array_filter($settings, static fn ($value) => $value !== null && $value !== ''));
    }

    public function createSignatureRequest(
        Deal $deal,
        GeneratedDocument $document,
        array $signers,
        array $widgets,
        array $options = []
    ): DealSignatureRequest {
        if (!$this->configured($deal->tenant_id)) {
            throw new RuntimeException('OpenSign is not configured. Set OPENSIGN_API_TOKEN before sending a signature request.');
        }

        $disk = Storage::disk(config('filesystems.default', 'local'));
        $pdfPath = $document->pdf_path;

        if (!$pdfPath || !$disk->exists($pdfPath)) {
            throw new RuntimeException('The generated document PDF could not be found in configured storage.');
        }

        $requestRecord = DealSignatureRequest::withoutGlobalScopes()->create([
            'tenant_id' => $deal->tenant_id,
            'deal_id' => $deal->id,
            'generated_document_id' => $document->id,
            'document_type' => $options['document_type'] ?? 'wholesale_contract',
            'title' => $options['title'] ?? $document->name,
            'status' => 'sending',
            'signers' => $signers,
            'source_pdf_path' => $pdfPath,
            'archive_directory' => 'deals/' . $deal->id . '/executed',
            'opensign_template_id' => $options['opensign_template_id'] ?? null,
        ]);

        $payload = [
            'file' => base64_encode($disk->get($pdfPath)),
            'title' => $requestRecord->title,
            'note' => $options['note'] ?? 'Please review and sign this wholesale transaction document.',
            'description' => $options['description'] ?? 'InsulaCRM wholesale deal #' . $deal->id,
            'timeToCompleteDays' => (int) ($options['time_to_complete_days'] ?? 15),
            'signers' => $signers,
            'folderId' => $options['folder_id'] ?? '',
            'send_email' => true,
            'email_subject' => $options['email_subject'] ?? '{{sender_name}} has requested you to sign {{document_title}}',
            'email_body' => $options['email_body'] ?? '<p>Hi {{receiver_name}},</p><p>Please review and sign <strong>{{document_title}}</strong>.</p><p><a href="{{signing_url}}">Sign here</a></p>',
            'sendInOrder' => (bool) ($options['send_in_order'] ?? true),
            'enableOTP' => (bool) ($options['enable_otp'] ?? true),
            'enableTour' => false,
            'redirect_url' => $options['redirect_url'] ?? '',
            'sender_name' => $options['sender_name'] ?? $this->settings($deal->tenant_id)['sender_name'] ?? config('services.opensign.sender_name', config('app.name')),
            'sender_email' => $options['sender_email'] ?? $this->settings($deal->tenant_id)['sender_email'] ?? config('services.opensign.sender_email'),
            'allow_modifications' => false,
            'auto_reminder' => (bool) ($options['auto_reminder'] ?? true),
            'remind_once_in_every' => (int) ($options['remind_once_in_every'] ?? 3),
        ];

        foreach ($payload['signers'] as $index => $signer) {
            if (!isset($signer['widgets']) && isset($widgets[$index])) {
                $payload['signers'][$index]['widgets'] = $widgets[$index];
            }
        }

        $templateId = $options['opensign_template_id'] ?? $this->defaultTemplateId($deal->tenant_id, $options['document_type'] ?? 'wholesale_contract');
        $endpoint = $templateId ? '/createdocument/' . rawurlencode($templateId) : '/createdocument';

        if ($templateId) {
            unset($payload['file']);
        }

        $response = $this->request('POST', $endpoint, $payload, $deal->tenant_id);
        $body = $response->json();

        if (!$response->successful() || empty($body['objectId'])) {
            $requestRecord->update([
                'status' => 'failed',
                'last_event' => 'create_failed',
                'last_event_payload' => $body ?: ['status' => $response->status()],
                'last_event_at' => now(),
            ]);

            throw new RuntimeException($body['error'] ?? 'OpenSign failed to create the signature request.');
        }

        $requestRecord->update([
            'status' => 'sent',
            'opensign_object_id' => $body['objectId'],
            'signing_urls' => $body['signurl'] ?? [],
            'sent_at' => now(),
            'expires_at' => now()->addDays((int) ($options['time_to_complete_days'] ?? 15)),
        ]);

        Activity::withoutGlobalScopes()->create([
            'tenant_id' => $deal->tenant_id,
            'lead_id' => $deal->lead_id,
            'agent_id' => $deal->agent_id,
            'type' => 'document',
            'subject' => 'Signature request sent',
            'body' => 'OpenSign signature request sent for deal #' . $deal->id . ': ' . $requestRecord->title,
            'logged_at' => now(),
        ]);

        return $requestRecord->fresh();
    }

    public function handleWebhook(array $payload): ?DealSignatureRequest
    {
        $objectId = $payload['objectId'] ?? null;

        if (!$objectId) {
            return null;
        }

        $requestRecord = DealSignatureRequest::withoutGlobalScopes()
            ->where('opensign_object_id', $objectId)
            ->first();

        if (!$requestRecord) {
            return null;
        }

        $event = strtolower((string) ($payload['event'] ?? 'unknown'));
        $wasCompleted = $requestRecord->status === 'completed' || !empty($requestRecord->completed_at);

        $updates = [
            'last_event' => $event,
            'last_event_payload' => $payload,
            'last_event_at' => now(),
        ];

        if ($event === 'signed') {
            $updates['status'] = 'partially_signed';
        } elseif ($event === 'completed') {
            $updates['status'] = 'completed';
            $updates['completed_at'] = $payload['completedAt'] ?? now();
            $updates['external_file_url'] = $payload['file'] ?? null;
            $updates['external_certificate_url'] = $payload['certificate'] ?? null;
        } elseif ($event === 'declined') {
            $updates['status'] = 'declined';
            $updates['declined_at'] = $payload['declinedAt'] ?? now();
            $updates['declined_reason'] = $payload['declinedReason'] ?? 'Signer declined the document.';
        } elseif ($event === 'viewed') {
            $updates['status'] = 'viewed';
        }

        $requestRecord->update($updates);

        if ($event === 'completed' && !$wasCompleted) {
            $deal = $requestRecord->deal()->withoutGlobalScopes()->firstOrFail();

            $completionActivityExists = Activity::withoutGlobalScopes()
                ->where('deal_id', $deal->id)
                ->where('subject', 'Contract fully executed')
                ->exists();

            if (!$completionActivityExists) {
                Activity::withoutGlobalScopes()->create([
                    'tenant_id' => $deal->tenant_id,
                    'lead_id' => $deal->lead_id,
                    'deal_id' => $deal->id,
                    'agent_id' => $deal->agent_id,
                    'type' => 'document',
                    'subject' => 'Contract fully executed',
                    'body' => 'OpenSign completed the signature request for deal #' . $deal->id . '. Executed documents will be archived under ' . ($requestRecord->archive_directory ?: 'deals/' . $deal->id . '/executed') . '.',
                    'logged_at' => now(),
                ]);
            }
        }

        if ($event === 'completed' && (empty($requestRecord->signed_pdf_path) || empty($requestRecord->certificate_path))) {
            $this->archiveCompletedDocument($requestRecord, $payload, !$wasCompleted);
        }

        return $requestRecord->fresh();
    }

    protected function archiveCompletedDocument(
        DealSignatureRequest $requestRecord,
        array $payload,
        bool $emitCompletionEvent = true
    ): void
    {
        $deal = $requestRecord->deal()->withoutGlobalScopes()->with(['lead.property', 'committedBuyer'])->firstOrFail();
        $disk = Storage::disk(config('filesystems.default', 'local'));
        $directory = $requestRecord->archive_directory ?: 'deals/' . $deal->id . '/executed';

        if (empty($requestRecord->signed_pdf_path) && !empty($payload['file'])) {
            $signed = Http::timeout(60)->get($payload['file']);

            if ($signed->successful()) {
                $filename = $this->safeFilename($requestRecord->title) . '-executed.pdf';
                $path = $directory . '/' . $filename;
                if (!$disk->exists($path)) {
                    $disk->put($path, $signed->body());
                    DealDocument::withoutGlobalScopes()->create([
                        'tenant_id' => $deal->tenant_id,
                        'deal_id' => $deal->id,
                        'filename' => $filename,
                        'original_name' => $filename,
                        'mime_type' => 'application/pdf',
                        'size' => strlen($signed->body()),
                        'path' => $path,
                    ]);
                }
                $requestRecord->update(['signed_pdf_path' => $path]);
            }
        }

        if (empty($requestRecord->certificate_path) && !empty($payload['certificate'])) {
            $certificate = Http::timeout(60)->get($payload['certificate']);

            if ($certificate->successful()) {
                $filename = $this->safeFilename($requestRecord->title) . '-completion-certificate.pdf';
                $path = $directory . '/' . $filename;
                if (!$disk->exists($path)) {
                    $disk->put($path, $certificate->body());
                    DealDocument::withoutGlobalScopes()->create([
                        'tenant_id' => $deal->tenant_id,
                        'deal_id' => $deal->id,
                        'filename' => $filename,
                        'original_name' => $filename,
                        'mime_type' => 'application/pdf',
                        'size' => strlen($certificate->body()),
                        'path' => $path,
                    ]);
                }
                $requestRecord->update(['certificate_path' => $path]);
            }
        }

        if (!$emitCompletionEvent) {
            return;
        }

        WebhookService::dispatch('deal.contract.executed', [
            'deal_id' => $deal->id,
            'lead_id' => $deal->lead_id,
            'signature_request_id' => $requestRecord->id,
            'opensign_object_id' => $requestRecord->opensign_object_id,
            'document_type' => $requestRecord->document_type,
            'status' => 'completed',
            'signed_pdf_path' => $requestRecord->signed_pdf_path,
            'certificate_path' => $requestRecord->certificate_path,
            'completed_at' => $requestRecord->completed_at,
        ], $deal->tenant_id);
    }

    protected function defaultTemplateId(?int $tenantId, string $documentType): ?string
    {
        if (!$tenantId) {
            return null;
        }

        $templateIds = $this->settings($tenantId)['template_ids'] ?? [];
        $key = match ($documentType) {
            'assignment' => 'assignment',
            'double_close' => 'double_close',
            'novation' => 'novation',
            default => 'purchase_agreement',
        };

        $templateId = $templateIds[$key] ?? null;
        return $templateId !== '' ? $templateId : null;
    }

    protected function request(string $method, string $path, array $payload = [], ?int $tenantId = null): Response
    {
        $settings = $this->settings($tenantId);
        $baseUrl = rtrim($settings['base_url'] ?: 'https://sandbox.opensignlabs.com/api/v1.2', '/');

        return Http::acceptJson()
            ->withHeaders(['x-api-token' => $settings['api_token']])
            ->timeout(90)
            ->{$method}($baseUrl . $path, $payload);
    }

    protected function safeFilename(string $name): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', $name), '-') ?: 'document';
    }
}
