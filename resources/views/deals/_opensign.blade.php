@if(($businessMode ?? 'wholesale') === 'wholesale')
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">E-Signature & Executed Documents</h3>
        <div class="card-actions">
            @if(config('services.opensign.api_token'))
                <span class="badge bg-green-lt">OpenSign configured</span>
            @else
                <span class="badge bg-yellow-lt">OpenSign not configured</span>
            @endif
        </div>
    </div>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="text-secondary small mb-2">Generated contract PDFs</div>

                @forelse($deal->generatedDocuments as $document)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $document->name }}</div>
                        <div class="text-secondary small mb-2">
                            Created {{ $document->created_at?->format('M j, Y g:i A') }}
                        </div>

                        @if(config('services.opensign.api_token') && $deal->contract_gate_status === 'approved')
                            <form method="POST" action="{{ route('deals.sendOpenSign', $deal) }}">
                                @csrf
                                <input type="hidden" name="generated_document_id" value="{{ $document->id }}">

                                <div class="d-flex flex-wrap gap-3 mb-2">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="include_seller" value="1" {{ $deal->lead?->email ? 'checked' : 'disabled' }}>
                                        <span class="form-check-label">
                                            Seller
                                            @if($deal->lead?->email)
                                                <span class="text-secondary">{{ $deal->lead->email }}</span>
                                            @else
                                                <span class="text-danger">No email</span>
                                            @endif
                                        </span>
                                    </label>

                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="include_buyer" value="1" {{ $deal->committedBuyer?->email ? 'checked' : 'disabled' }}>
                                        <span class="form-check-label">
                                            Buyer
                                            @if($deal->committedBuyer?->email)
                                                <span class="text-secondary">{{ $deal->committedBuyer->email }}</span>
                                            @else
                                                <span class="text-danger">No email</span>
                                            @endif
                                        </span>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-purple btn-sm">
                                    Send for Signature
                                </button>
                            </form>
                        @elseif($deal->contract_gate_status !== 'approved')
                            <div class="text-warning small">
                                Contract Protection Gate approval is required before sending.
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-secondary">No generated PDF contracts are attached to this deal yet.</div>
                @endforelse
            </div>

            <div class="col-lg-6">
                <div class="text-secondary small mb-2">Signature history</div>

                @forelse($deal->signatureRequests as $request)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold">{{ $request->title }}</div>
                            <span class="badge {{ $request->status === 'completed' ? 'bg-green-lt' : ($request->status === 'declined' ? 'bg-red-lt' : 'bg-blue-lt') }}">
                                {{ str_replace('_', ' ', ucfirst($request->status)) }}
                            </span>
                        </div>
                        <div class="text-secondary small mt-1">
                            @if($request->sent_at)
                                Sent {{ $request->sent_at->format('M j, Y g:i A') }}
                            @endif
                            @if($request->completed_at)
                                · Completed {{ $request->completed_at->format('M j, Y g:i A') }}
                            @endif
                        </div>

                        @if($request->signed_pdf_path)
                            <div class="mt-2">
                                <span class="badge bg-green-lt">Executed PDF archived</span>
                            </div>
                        @endif

                        @if($request->certificate_path)
                            <div class="mt-1">
                                <span class="badge bg-green-lt">Completion certificate archived</span>
                            </div>
                        @endif

                        @if($request->signing_urls && $request->status !== 'completed')
                            <div class="mt-2">
                                @foreach($request->signing_urls as $signing)
                                    @if(!empty($signing['url']))
                                        <a class="btn btn-outline-secondary btn-sm me-1 mb-1" href="{{ $signing['url'] }}" target="_blank" rel="noopener">
                                            Open {{ $signing['email'] ?? 'signing link' }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-secondary">No signature requests have been sent for this deal.</div>
                @endforelse
            </div>
        </div>

        <div class="alert alert-info mt-3 mb-0">
            Executed documents are stored in the deal's secure storage path under deals/{{ $deal->id }}/executed/.
            They are not stored in GitHub.
        </div>
    </div>
</div>
@endif
