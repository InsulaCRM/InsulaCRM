@extends('layouts.app')

@section('title', __('Disposition Room') . ' — ' . $deal->title)
@section('page-title', __('Disposition Room'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('pipeline') }}">{{ __('Pipeline') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('deals.show', $deal) }}">{{ $deal->title }}</a></li>
<li class="breadcrumb-item active">{{ __('Disposition Room') }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Deal Summary (Left) -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Deal Summary') }}</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">{{ __('Title') }}</dt>
                    <dd class="col-7">{{ $deal->title }}</dd>
                    <dt class="col-5">{{ __('Stage') }}</dt>
                    <dd class="col-7"><span class="badge bg-blue-lt">{{ \App\Models\Deal::stageLabel($deal->stage) }}</span></dd>
                    <dt class="col-5">{{ __('Contract Price') }}</dt>
                    <dd class="col-7">{{ $deal->contract_price ? Fmt::currency($deal->contract_price) : '—' }}</dd>
                    <dt class="col-5">{{ __('Assignment Fee') }}</dt>
                    <dd class="col-7">{{ $deal->assignment_fee ? Fmt::currency($deal->assignment_fee) : '—' }}</dd>
                    @if($deal->lead?->property)
                    <dt class="col-5">{{ __('Property') }}</dt>
                    <dd class="col-7">{{ $deal->lead->property->address ?? '—' }}</dd>
                    @endif
                    <dt class="col-5">{{ __('Agent') }}</dt>
                    <dd class="col-7">{{ $deal->agent->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary w-100" id="mass-email-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
                        {{ __('Email All Uncontacted') }}
                    </button>
                    <button class="btn btn-info w-100" id="mass-sms-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h8"/><path d="M8 13h6"/><path d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z"/></svg>
                        {{ __('SMS All Uncontacted') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Buyer Cards (Right) -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Matched Buyers') }} ({{ $matches->count() }})</h3>
                <div class="card-actions">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active status-filter" data-status="all">{{ __('All') }}</button>
                        <button class="btn btn-outline-secondary status-filter" data-status="pending">{{ __('Pending') }}</button>
                        <button class="btn btn-outline-secondary status-filter" data-status="contacted">{{ __('Contacted') }}</button>
                        <button class="btn btn-outline-secondary status-filter" data-status="interested">{{ __('Interested') }}</button>
                        <button class="btn btn-outline-secondary status-filter" data-status="passed">{{ __('Passed') }}</button>
                    </div>
                </div>
            </div>
            <div class="list-group list-group-flush" id="buyer-list">
                @forelse($matches as $match)
                <div class="list-group-item buyer-card" data-match-id="{{ $match->id }}" data-status="{{ $match->outreach_status }}">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-{{ $match->match_score >= 80 ? 'green' : ($match->match_score >= 50 ? 'yellow' : 'red') }}-lt">{{ $match->match_score }}%</span>
                        </div>
                        <div class="col">
                            <div class="fw-bold">{{ $match->buyer->first_name ?? '' }} {{ $match->buyer->last_name ?? '' }}</div>
                            <div class="text-secondary small">
                                {{ $match->buyer->company ?? '' }}
                                @if($match->buyer->phone) &middot; {{ $match->buyer->phone }} @endif
                                @if($match->buyer->email) &middot; {{ $match->buyer->email }} @endif
                            </div>
                            @if($match->buyer_notes)
                            <div class="text-muted small mt-1"><em>{{ $match->buyer_notes }}</em></div>
                            @endif
                        </div>
                        <div class="col-auto">
                            <select class="form-select form-select-sm outreach-status-select" data-match-id="{{ $match->id }}" style="width: auto;">
                                @foreach(['pending', 'contacted', 'interested', 'passed', 'offered', 'closed'] as $s)
                                <option value="{{ $s }}" {{ $match->outreach_status === $s ? 'selected' : '' }}>{{ __(ucfirst($s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-outline-secondary add-notes-btn" data-match-id="{{ $match->id }}" data-notes="{{ e($match->buyer_notes ?? '') }}" title="{{ __('Notes') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4"/><line x1="13.5" y1="6.5" x2="17.5" y2="10.5"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center text-secondary py-4">{{ __('No matched buyers for this deal.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.outreach-status-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        const matchId = this.dataset.matchId;
        fetch('{{ url("/disposition") }}/' + matchId + '/status', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            body: JSON.stringify({ outreach_status: this.value })
        });
        this.closest('.buyer-card').dataset.status = this.value;
    });
});

document.querySelectorAll('.status-filter').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.status-filter').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        const status = this.dataset.status;
        document.querySelectorAll('.buyer-card').forEach(function(card) {
            card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
        });
    });
});

document.querySelectorAll('.add-notes-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const matchId = this.dataset.matchId;
        const notes = prompt('{{ __("Buyer notes:") }}', this.dataset.notes || '');
        if (notes === null) return;
        fetch('{{ url("/disposition") }}/' + matchId + '/status', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            body: JSON.stringify({ outreach_status: document.querySelector('.outreach-status-select[data-match-id="' + matchId + '"]').value, buyer_notes: notes })
        });
        this.dataset.notes = notes;
    });
});

function massOutreach(channel) {
    if (!confirm('{{ __("Send :channel to all uncontacted buyers?") }}'.replace(':channel', channel))) return;
    fetch('{{ url("/disposition/" . $deal->id . "/mass-outreach") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ channel: channel })
    }).then(function(r) { return r.json(); }).then(function(res) {
        alert(res.message || '{{ __("Done") }}');
        location.reload();
    });
}
document.getElementById('mass-email-btn').addEventListener('click', function() { massOutreach('email'); });
document.getElementById('mass-sms-btn').addEventListener('click', function() { massOutreach('sms'); });
</script>
@endpush
@endsection