@extends('layouts.app')

@section('title', __('Contract Archive'))

@section('content')
<div class="page-header d-print-none">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">{{ __('Wholesale') }}</div>
            <h2 class="page-title">{{ __('Contract Archive') }}</h2>
            <div class="text-muted mt-1">{{ __('Completed e-signature packages and executed documents.') }}</div>
        </div>
        <div class="col-auto">
            <a href="{{ route('pipeline') }}" class="btn btn-outline-secondary">{{ __('Back to Pipeline') }}</a>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('deals.archive') }}">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                               placeholder="{{ __('Search address, seller, buyer, email...') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="structure" class="form-select">
                            <option value="">{{ __('All structures') }}</option>
                            @foreach($structures as $value => $label)
                                <option value="{{ $value }}" @selected(request('structure') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="stage" class="form-select">
                            <option value="">{{ __('All stages') }}</option>
                            @foreach($stages as $value => $label)
                                <option value="{{ $value }}" @selected(request('stage') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">{{ __('Search') }}</button>
                        <a href="{{ route('deals.archive') }}" class="btn btn-outline-secondary">{{ __('Clear') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @forelse($deals as $deal)
        @php
            $property = $deal->lead?->property;
            $completedSignature = $deal->signatureRequests->first();
            $seller = $deal->lead?->full_name ?: '—';
            $buyer = $deal->committedBuyer?->name ?: ($deal->buyer_legal_name ?: '—');
            $signedDocument = $completedSignature?->signed_pdf_path
                ? $deal->documents->firstWhere('path', $completedSignature->signed_pdf_path)
                : null;
            $certificateDocument = $completedSignature?->certificate_path
                ? $deal->documents->firstWhere('path', $completedSignature->certificate_path)
                : null;
        @endphp

        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-lg-5">
                        <div class="fw-bold">{{ $property?->address ?: $deal->title }}</div>
                        <div class="text-muted small">
                            {{ $property?->city }}{{ $property?->city && $property?->state ? ', ' : '' }}{{ $property?->state }} {{ $property?->zip_code }}
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-green-lt">{{ __('Executed') }}</span>
                            <span class="badge bg-azure-lt">{{ $deal->transaction_structure ? ucfirst(str_replace('_', ' ', $deal->transaction_structure)) : __('Wholesale') }}</span>
                            <span class="badge bg-secondary-lt">{{ AppModelsDeal::stageLabel($deal->stage) }}</span>
                        </div>
                    </div>

                    <div class="col-lg-3 mt-3 mt-lg-0">
                        <div class="text-muted small">{{ __('Seller') }}</div>
                        <div class="fw-semibold">{{ $seller }}</div>
                        <div class="text-muted small mt-2">{{ __('Buyer') }}</div>
                        <div class="fw-semibold">{{ $buyer }}</div>
                    </div>

                    <div class="col-lg-2 mt-3 mt-lg-0">
                        <div class="text-muted small">{{ __('Signed') }}</div>
                        <div class="fw-semibold">
                            {{ optional($completedSignature?->completed_at)->format('M j, Y g:i A') ?: '—' }}
                        </div>
                        <div class="text-muted small mt-2">{{ __('Contract price') }}</div>
                        <div class="fw-semibold">
                            {{ $deal->contract_price !== null ? '$'.number_format((float) $deal->contract_price, 2) : '—' }}
                        </div>
                    </div>

                    <div class="col-lg-2 mt-3 mt-lg-0 text-lg-end">
                        <a href="{{ route('deals.show', $deal) }}" class="btn btn-outline-primary btn-sm mb-1">{{ __('Open Deal') }}</a>
                        @if($signedDocument)
                            <a href="{{ route('deals.downloadDocument', $signedDocument) }}" class="btn btn-success btn-sm mb-1">{{ __('Executed PDF') }}</a>
                        @endif
                        @if($certificateDocument)
                            <a href="{{ route('deals.downloadDocument', $certificateDocument) }}" class="btn btn-outline-secondary btn-sm">{{ __('Certificate') }}</a>
                        @endif
                    </div>
                </div>

                <div class="border-top mt-3 pt-3">
                    <div class="row g-2 small text-muted">
                        <div class="col-md-4"><strong>{{ __('Archive folder') }}:</strong> {{ $completedSignature?->archive_directory ?: '—' }}</div>
                        <div class="col-md-4"><strong>{{ __('Document') }}:</strong> {{ $completedSignature?->title ?: '—' }}</div>
                        <div class="col-md-4"><strong>{{ __('Deal #') }}:</strong> {{ $deal->id }}</div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="h3">{{ __('No executed deals found') }}</div>
                <div class="text-muted">{{ __('Completed OpenSign contracts will appear here automatically after the completion webhook archives the signed PDF and certificate.') }}</div>
            </div>
        </div>
    @endforelse

    @if($deals->hasPages())
        <div class="d-flex justify-content-center mt-3">{{ $deals->links() }}</div>
    @endif
</div>
@endsection
