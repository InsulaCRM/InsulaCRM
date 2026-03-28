@extends('layouts.app')

@section('title', __('Generate Document'))
@section('page-title', __('Generate Document'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('pipeline') }}">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transactions') : __('Pipeline') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('deals.show', $deal) }}">{{ $deal->title ?? $deal->lead->full_name ?? ($modeTerms['deal_label'] ?? __('Deal')) }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Generate Document') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        {{-- Deal Info Summary --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-1">{{ $deal->title ?? ($modeTerms['deal_label'] ?? __('Deal')) }}</h3>
                        <div class="text-secondary">
                            <span class="badge bg-primary-lt me-1">{{ \App\Models\Deal::stageLabel($deal->stage) }}</span>
                            @if($deal->lead)
                                <span class="me-2">{{ __('Seller:') }} {{ $deal->lead->full_name }}</span>
                            @endif
                            @if($deal->contract_price)
                                <span>{{ __('Price:') }} {{ Fmt::currency($deal->contract_price) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('deals.show', $deal) }}" class="btn btn-outline-secondary btn-sm">
                            {{ __('Back to Deal') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Generate New Document --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Generate New Document') }}</h3>
            </div>
            <div class="card-body">
                @if($templates->count())
                <form method="POST" action="{{ route('documents.store', $deal) }}" id="generate-form">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('Select Template') }}</label>
                            <select name="template_id" id="template-select" class="form-select" required>
                                <option value="">{{ __('Choose a template...') }}</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" data-type="{{ $template->type }}">
                                        {{ $template->name }}
                                        ({{ \App\Models\DocumentTemplate::typeLabel($template->type) }})
                                        {{ $template->is_default ? ' *' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Document Name') }} <small class="text-secondary">({{ __('optional') }})</small></label>
                            <input type="text" name="name" class="form-control" placeholder="{{ __('Auto-generated from template + deal name') }}">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="generate-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 11v6"/><path d="M9 14l3 -3l3 3"/></svg>
                            {{ __('Generate Document') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="preview-deal-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                            {{ __('Preview with Deal Data') }}
                        </button>
                    </div>
                </form>
                @else
                <div class="empty py-4">
                    <p class="empty-title">{{ __('No templates available') }}</p>
                    <p class="empty-subtitle text-secondary">
                        {{ __('Create a document template first to generate documents for this deal.') }}
                    </p>
                    <div class="empty-action">
                        <a href="{{ route('document-templates.create') }}" class="btn btn-primary">
                            {{ __('Create Template') }}
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Live Preview Area --}}
        <div class="card mb-3" id="preview-card" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">{{ __('Document Preview') }}</h3>
                <div class="card-actions">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="close-preview-btn">{{ __('Close Preview') }}</button>
                </div>
            </div>
            <div class="card-body">
                <div id="preview-loading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('Loading preview...') }}</p>
                </div>
                <div id="preview-content" style="border: 1px solid #e6e8eb; padding: 20px; background: #fff; min-height: 200px;"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Previously Generated Documents --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Generated Documents') }}</h3>
                <div class="card-actions">
                    <span class="badge bg-secondary-lt">{{ $generatedDocuments->count() }}</span>
                </div>
            </div>
            @if($generatedDocuments->count())
            <div class="list-group list-group-flush">
                @foreach($generatedDocuments as $doc)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <a href="{{ route('documents.show', $doc) }}" class="text-reset fw-medium">{{ $doc->name }}</a>
                            <div class="text-secondary small">
                                @if($doc->template)
                                    <span class="badge bg-secondary-lt">{{ \App\Models\DocumentTemplate::typeLabel($doc->template->type) }}</span>
                                @endif
                                {{ $doc->created_at->diffForHumans() }}
                                @if($doc->user)
                                    &middot; {{ $doc->user->name }}
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                            </a>
                            <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this document?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="card-body">
                <p class="text-secondary mb-0">{{ __('No documents generated for this deal yet.') }}</p>
            </div>
            @endif
        </div>

        {{-- Deal Data Summary --}}
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Data Used in Merge') }}</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h4 class="text-secondary mb-1">{{ __('Seller') }}</h4>
                    @if($deal->lead)
                        <div>{{ $deal->lead->full_name }}</div>
                        <div class="text-secondary small">{{ $deal->lead->phone }} {{ $deal->lead->email ? '| ' . $deal->lead->email : '' }}</div>
                    @else
                        <span class="text-warning">{{ __('No lead attached') }}</span>
                    @endif
                </div>
                <div class="mb-3">
                    <h4 class="text-secondary mb-1">{{ __('Property') }}</h4>
                    @if($deal->lead && $deal->lead->property)
                        <div>{{ $deal->lead->property->full_address }}</div>
                    @else
                        <span class="text-warning">{{ __('No property data') }}</span>
                    @endif
                </div>
                <div class="mb-3">
                    <h4 class="text-secondary mb-1">{{ __('Buyer (Top Match)') }}</h4>
                    @php $topBuyer = $deal->buyerMatches->sortByDesc('match_score')->first(); @endphp
                    @if($topBuyer && $topBuyer->buyer)
                        <div>{{ $topBuyer->buyer->first_name }} {{ $topBuyer->buyer->last_name }}</div>
                        <div class="text-secondary small">{{ $topBuyer->buyer->company }}</div>
                    @else
                        <span class="text-warning">{{ __('No buyer matched') }}</span>
                    @endif
                </div>
                <div>
                    <h4 class="text-secondary mb-1">{{ __('Financials') }}</h4>
                    <div class="text-secondary small">
                        {{ __('Contract Price:') }} {{ Fmt::currency($deal->contract_price) }}<br>
                        @if(($businessMode ?? 'wholesale') === 'realestate')
                            {{ __('Commission:') }} {{ Fmt::currency($deal->total_commission) }}<br>
                        @else
                            {{ __('Assignment Fee:') }} {{ Fmt::currency($deal->assignment_fee) }}<br>
                        @endif
                        {{ __('Earnest Money:') }} {{ Fmt::currency($deal->earnest_money) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var templateSelect = document.getElementById('template-select');
    var generateBtn = document.getElementById('generate-btn');
    var previewBtn = document.getElementById('preview-deal-btn');
    var previewCard = document.getElementById('preview-card');
    var previewLoading = document.getElementById('preview-loading');
    var previewContent = document.getElementById('preview-content');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Enable/disable buttons on template select
    templateSelect.addEventListener('change', function() {
        var hasValue = !!this.value;
        generateBtn.disabled = !hasValue;
        previewBtn.disabled = !hasValue;

        if (hasValue) {
            loadPreview(this.value);
        }
    });

    // Preview button
    previewBtn.addEventListener('click', function() {
        if (templateSelect.value) {
            loadPreview(templateSelect.value);
        }
    });

    // Close preview
    document.getElementById('close-preview-btn').addEventListener('click', function() {
        previewCard.style.display = 'none';
    });

    function loadPreview(templateId) {
        previewCard.style.display = 'block';
        previewLoading.style.display = 'block';
        previewContent.innerHTML = '';

        fetch('{{ url("/documents/preview-deal/" . $deal->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ template_id: templateId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            previewLoading.style.display = 'none';
            previewContent.innerHTML = data.html || '';
        })
        .catch(function() {
            previewLoading.style.display = 'none';
            previewContent.innerHTML = '<div class="alert alert-danger">{{ __('Failed to load preview.') }}</div>';
        });
    }
});
</script>
@endpush
