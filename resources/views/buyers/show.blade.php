@extends('layouts.app')

@section('title', $buyer->full_name)
@section('page-title', $buyer->full_name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('buyers.index') }}">{{ $modeTerms['buyer_label'] ?? __('Buyers') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $buyer->full_name }}</li>
@endsection

@section('content')
<div class="row row-deck row-cards">
    {{-- AI Briefing (auto-loads) --}}
    @if(auth()->user()->tenant->ai_enabled && auth()->user()->tenant->ai_briefings_enabled)
    <div class="col-12">
        <div class="card" id="buyer-briefing-card" style="border-left: 3px solid #ae3ec9; background: linear-gradient(135deg, rgba(174,62,201,0.03) 0%, rgba(174,62,201,0.07) 100%);">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm bg-purple text-white me-2 flex-shrink-0" style="width: 28px; height: 28px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        </span>
                        <div>
                            <div class="fw-bold" style="font-size: 0.85rem; line-height: 1.2;">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('AI Client Briefing') : __('AI Buyer Briefing') }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">{{ __('Auto-generated summary') }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost-purple btn-sm px-2" id="buyer-briefing-refresh" title="{{ __('Refresh') }}" style="display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                        <span style="font-size: 0.7rem;">{{ __('Refresh') }}</span>
                    </button>
                </div>
                <div id="buyer-briefing-loading" style="font-size: 0.82rem;">
                    <span class="spinner-border spinner-border-sm text-purple me-1" style="width: 0.75rem; height: 0.75rem;"></span>
                    <span class="text-secondary">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Generating client briefing...') : __('Generating buyer briefing...') }}</span>
                </div>
                <div id="buyer-briefing-text" style="font-size: 0.82rem; line-height: 1.6; display: none; color: #334155;"></div>
                <div id="buyer-briefing-links" style="display: none;" class="mt-2 pt-2 d-flex flex-wrap gap-1"></div>
                <div id="buyer-briefing-error" style="font-size: 0.82rem; display: none;" class="text-danger"></div>
            </div>
        </div>
    </div>
    @endif

    {{-- Buyer Information --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Client Details') : __('Buyer Details') }}</h3>
                <div class="card-actions">
                    <a href="{{ route('buyers.edit', $buyer) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Full Name') }}</div>
                        <div class="datagrid-content">{{ $buyer->full_name }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Company') }}</div>
                        <div class="datagrid-content">{{ $buyer->company ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Phone') }}</div>
                        <div class="datagrid-content">@if($buyer->phone)<a href="tel:{{ $buyer->phone }}" class="text-reset"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/></svg> {{ $buyer->phone }}</a>@else - @endif</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Email') }}</div>
                        <div class="datagrid-content">@if($buyer->email)<a href="mailto:{{ $buyer->email }}" class="text-reset"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg> {{ $buyer->email }}</a>@else - @endif</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Budget') : __('Max Purchase Price') }}</div>
                        <div class="datagrid-content">{{ Fmt::currency($buyer->max_purchase_price) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Total') }} {{ $modeTerms['deal_label'] ?? __('Deals') }}s {{ __('Closed') }}</div>
                        <div class="datagrid-content">{{ $buyer->total_deals_closed ?? 0 }}</div>
                    </div>
                </div>
                @if($buyer->notes)
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="datagrid-title">{{ __('Notes') }}</div>
                            <a href="#" class="small text-muted" id="toggle-notes" style="display:none;" onclick="event.preventDefault(); var el=document.getElementById('buyer-notes-box'); var open=el.style.maxHeight!=='4.5rem'; el.style.maxHeight=open?'4.5rem':'none'; el.style.overflow=open?'hidden':'visible'; this.textContent=open?'{{ __('Show more') }}':'{{ __('Show less') }}';">{{ __('Show more') }}</a>
                        </div>
                        <div id="buyer-notes-box" class="text-secondary" style="white-space: pre-wrap; max-height: 4.5rem; overflow: hidden;">{{ $buyer->notes }}</div>
                    </div>
                    <script>document.addEventListener('DOMContentLoaded', function(){ var el=document.getElementById('buyer-notes-box'); if(el.scrollHeight > el.clientHeight){ document.getElementById('toggle-notes').style.display=''; } });</script>
                @endif
            </div>
        </div>
    </div>


    {{-- Buyer Verification & Score --}}
    @include('buyers._verification_card', ['buyer' => $buyer])

    {{-- Investment Criteria --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Preferences') : __('Investment Criteria') }}</h3>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Preferred Property Types') }}</div>
                        <div class="datagrid-content">
                            @if($buyer->preferred_property_types && count($buyer->preferred_property_types))
                                @foreach($buyer->preferred_property_types as $type)
                                    <span class="badge bg-blue-lt me-1">{{ __(ucwords(str_replace('_', ' ', $type))) }}</span>
                                @endforeach
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Preferred States') }}</div>
                        <div class="datagrid-content">
                            @if($buyer->preferred_states && count($buyer->preferred_states))
                                @foreach($buyer->preferred_states as $state)
                                    <span class="badge bg-cyan-lt me-1">{{ $state }}</span>
                                @endforeach
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Preferred Zip Codes') }}</div>
                        <div class="datagrid-content">
                            @if($buyer->preferred_zip_codes && count($buyer->preferred_zip_codes))
                                @foreach($buyer->preferred_zip_codes as $zip)
                                    <span class="badge bg-purple-lt me-1">{{ $zip }}</span>
                                @endforeach
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </div>
                    </div>
                    @if(($businessMode ?? 'wholesale') !== 'realestate')
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Asset Classes') }}</div>
                        <div class="datagrid-content">
                            @if($buyer->asset_classes && count($buyer->asset_classes))
                                @foreach($buyer->asset_classes as $class)
                                    <span class="badge bg-green-lt me-1">{{ __(str_replace('_', ' ', strtoupper($class))) }}</span>
                                @endforeach
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Deal Matches --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrows-exchange me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 10h14l-4 -4"/><path d="M17 14h-14l4 4"/></svg>
                    {{ $modeTerms['deal_label'] ?? __('Deal') }} {{ __('Matches') }}
                </h3>
                @if($buyer->dealMatches->count())
                <div class="card-actions">
                    <span class="badge bg-blue-lt">{{ $buyer->dealMatches->count() }}</span>
                </div>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ $modeTerms['deal_label'] ?? __('Deal') }}</th>
                            <th>{{ __('Stage') }}</th>
                            <th>{{ __('Match') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Notified') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buyer->dealMatches->sortByDesc('match_score') as $match)
                        <tr>
                            <td>
                                <a href="{{ route('deals.show', $match->deal_id) }}" class="fw-bold">{{ $match->deal->title ?? __('Deal') . ' #' . $match->deal_id }}</a>
                                @if($match->deal && $match->deal->lead)
                                <div class="text-secondary small">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
                                    <a href="{{ route('leads.show', $match->deal->lead) }}" class="text-reset">{{ $match->deal->lead->full_name }}</a>
                                </div>
                                @endif
                                @if($match->deal && $match->deal->contract_price)
                                <div class="text-secondary small">{{ Fmt::currency($match->deal->contract_price) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($match->deal)
                                <span class="badge bg-primary-lt">{{ \App\Models\Deal::stageLabel($match->deal->stage) }}</span>
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $match->match_score >= 70 ? 'bg-green-lt' : ($match->match_score >= 40 ? 'bg-yellow-lt' : 'bg-red-lt') }}">{{ $match->match_score }}%</span>
                            </td>
                            <td>
                                @if($match->response)
                                    <span class="badge {{ $match->response === 'interested' ? 'bg-green-lt' : 'bg-red-lt' }}">{{ __(ucfirst($match->response)) }}</span>
                                @elseif($match->notified_at)
                                    <span class="badge bg-blue-lt">{{ __('Contacted') }}</span>
                                @else
                                    <span class="text-secondary">{{ __('Pending') }}</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $match->notified_at ? $match->notified_at->diffForHumans() : '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary">{{ __('No deal matches found.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->tenant->ai_enabled)
@push('scripts')
<script>
(function() {
    var briefingText = document.getElementById('buyer-briefing-text');
    var briefingLoading = document.getElementById('buyer-briefing-loading');
    var briefingError = document.getElementById('buyer-briefing-error');
    var briefingRefresh = document.getElementById('buyer-briefing-refresh');
    var briefingLinks = document.getElementById('buyer-briefing-links');
    if (!briefingText) return;
    var csrfTkn = document.querySelector('meta[name="csrf-token"]').content;

    var typeLabels = { deal: '{{ __("Deal") }}', lead: '{{ __("Lead") }}', buyer: '{{ ($businessMode ?? "wholesale") === "realestate" ? __("Client") : __("Buyer") }}', property: '{{ __("Property") }}' };
    var typeColors = { deal: 'bg-blue-lt', lead: 'bg-green-lt', buyer: 'bg-orange-lt', property: 'bg-cyan-lt' };
    function renderBriefingLinks(links) {
        if (!links || !links.length) { briefingLinks.style.display = 'none'; return; }
        briefingLinks.innerHTML = '<span class="text-muted fw-bold me-1" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;align-self:center;">{{ __("Related") }}:</span>';
        briefingLinks.style.display = '';
        briefingLinks.style.borderTop = '1px solid rgba(174,62,201,0.15)';
        links.forEach(function(link) {
            var a = document.createElement(link.url ? 'a' : 'span');
            if (link.url) { a.href = link.url; a.style.cursor = 'pointer'; }
            a.className = 'badge ' + (typeColors[link.type] || 'bg-secondary-lt') + ' text-decoration-none';
            a.style.fontSize = '0.75rem';
            a.style.padding = '0.3em 0.6em';
            var prefix = typeLabels[link.type] ? typeLabels[link.type] + ': ' : '';
            var text = prefix + link.label;
            if (link.stage) text += ' — ' + link.stage;
            if (link.score) text += ' (' + link.score + '%)';
            if (link.lead) text += ' — ' + link.lead;
            a.textContent = text;
            briefingLinks.appendChild(a);
        });
    }

    function loadBriefing(force) {
        briefingLoading.style.display = '';
        briefingText.style.display = 'none';
        briefingError.style.display = 'none';
        briefingRefresh.style.display = 'none';
        briefingLinks.style.display = 'none';
        var url = '{{ url("/ai/buyer-briefing") }}' + (force ? '?refresh=1' : '');
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfTkn, 'Accept': 'application/json' },
            body: JSON.stringify({ buyer_id: {{ $buyer->id }} })
        }).then(function(r) { return r.json(); }).then(function(res) {
            briefingLoading.style.display = 'none';
            if (res.error === 'disabled') {
                document.getElementById('buyer-briefing-card').style.display = 'none'; return;
            }
            if (res.briefing) {
                briefingText.textContent = res.briefing;
                briefingText.style.display = '';
                briefingRefresh.style.display = '';
                renderBriefingLinks(res.links);
            } else if (res.error) {
                briefingError.textContent = res.error;
                briefingError.style.display = '';
            }
        }).catch(function() {
            briefingLoading.style.display = 'none';
            briefingError.textContent = '{{ __('Could not load briefing.') }}';
            briefingError.style.display = '';
        });
    }
    loadBriefing(false);
    briefingRefresh.addEventListener('click', function() { loadBriefing(true); });
})();
</script>
@endpush
@endif
@endsection
