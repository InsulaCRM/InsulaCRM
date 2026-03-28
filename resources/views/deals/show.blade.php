@extends('layouts.app')

@section('title', (($businessMode ?? 'wholesale') === 'realestate' ? __('Transaction:') : __('Deal:')) . ' ' . ($deal->lead->full_name ?? $deal->title))
@section('page-title', ($businessMode ?? 'wholesale') === 'realestate' ? __('Transaction Details') : __('Deal Details'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('pipeline') }}">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transactions') : __('Pipeline') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $deal->lead->full_name ?? $deal->title }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- AI Briefing (auto-loads) -->
        @if(auth()->user()->tenant->ai_enabled && auth()->user()->tenant->ai_briefings_enabled)
        <div class="card mb-3" id="deal-briefing-card" style="border-left: 3px solid #ae3ec9; background: linear-gradient(135deg, rgba(174,62,201,0.03) 0%, rgba(174,62,201,0.07) 100%);">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm bg-purple text-white me-2 flex-shrink-0" style="width: 28px; height: 28px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        </span>
                        <div>
                            <div class="fw-bold" style="font-size: 0.85rem; line-height: 1.2;">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('AI Transaction Briefing') : __('AI Deal Briefing') }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">{{ __('Auto-generated summary') }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost-purple btn-sm px-2" id="deal-briefing-refresh" title="{{ __('Refresh') }}" style="display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                        <span style="font-size: 0.7rem;">{{ __('Refresh') }}</span>
                    </button>
                </div>
                <div id="deal-briefing-loading" style="font-size: 0.82rem;">
                    <span class="spinner-border spinner-border-sm text-purple me-1" style="width: 0.75rem; height: 0.75rem;"></span>
                    <span class="text-secondary">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Generating transaction briefing...') : __('Generating deal briefing...') }}</span>
                </div>
                <div id="deal-briefing-text" style="font-size: 0.82rem; line-height: 1.6; display: none; color: #334155;"></div>
                <div id="deal-briefing-links" style="display: none;" class="mt-2 pt-2 d-flex flex-wrap gap-1"></div>
                <div id="deal-briefing-error" style="font-size: 0.82rem; display: none;" class="text-danger"></div>
            </div>
        </div>
        @endif

        <!-- Deal Info Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transaction Information') : __('Deal Information') }}</h3>
                <div class="card-actions">
                    @if(auth()->user()->tenant->ai_enabled)
                    <button type="button" class="btn btn-outline-purple btn-sm" id="ai-analyze-btn" title="{{ __('AI Deal Analysis') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brain" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15.5 13a3.5 3.5 0 0 0 -3.5 3.5v1a3.5 3.5 0 0 0 7 0v-1.8"/><path d="M8.5 13a3.5 3.5 0 0 1 3.5 3.5v1a3.5 3.5 0 0 1 -7 0v-1.8"/><path d="M17.5 16a3.5 3.5 0 0 0 0 -7h-.5"/><path d="M19 9.3v-2.8a3.5 3.5 0 0 0 -7 0"/><path d="M6.5 16a3.5 3.5 0 0 1 0 -7h.5"/><path d="M5 9.3v-2.8a3.5 3.5 0 0 1 7 0v10"/></svg>
                        {{ __('AI Analysis') }}
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" id="ai-stage-advice-btn" title="{{ __('AI Stage Advice') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-bulb" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h1m8 -9v1m8 8h1m-15.4 -6.4l.7 .7m12.1 -.7l-.7 .7"/><path d="M9 16a5 5 0 1 1 6 0a3.5 3.5 0 0 0 -1 3a2 2 0 0 1 -4 0a3.5 3.5 0 0 0 -1 -3"/><path d="M9.7 17l4.6 0"/></svg>
                        {{ __('Stage Advice') }}
                    </button>
                    @endif
                    @if(($businessMode ?? 'wholesale') === 'realestate' && auth()->user()->tenant->ai_enabled)
                    <button type="button" class="btn btn-outline-purple btn-sm" id="marketing-kit-btn" title="{{ __('Marketing Kit') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 8a3 3 0 0 1 0 6"/><path d="M10 8v11a1 1 0 0 1 -1 1h-1a1 1 0 0 1 -1 -1v-5"/><path d="M12 8h0l4.524 -3.77a.9 .9 0 0 1 1.476 .692v12.156a.9 .9 0 0 1 -1.476 .692l-4.524 -3.77h-8a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h8"/></svg>
                        {{ __('Marketing Kit') }}
                    </button>
                    @endif
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <a href="{{ route('disposition.show', $deal) }}" class="btn btn-outline-cyan btn-sm" title="{{ __('Disposition Room') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                        {{ __('Disposition Room') }}
                    </a>
                    @endif
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <a href="{{ route('documents.investorPacket', $deal) }}" class="btn btn-outline-orange btn-sm" target="_blank" title="{{ __('Investor Packet') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
                        {{ __('Investor Packet') }}
                    </a>
                    @endif
                    <a href="{{ route('pipeline') }}" class="btn btn-outline-secondary btn-sm">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Back to Transactions') : __('Back to Pipeline') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Stage') }}</div>
                        <div class="datagrid-content">
                            <span class="badge bg-primary-lt">{{ \App\Models\Deal::stageLabel($deal->stage) }}</span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Agent') }}</div>
                        <div class="datagrid-content">{{ $deal->agent->name ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Matched') }} {{ $modeTerms['buyer_label'] ?? __('Buyers') }}</div>
                        <div class="datagrid-content">
                            @if($deal->buyerMatches->count())
                                <span class="badge bg-green-lt">{{ $deal->buyerMatches->count() }}</span>
                                <span class="text-secondary small ms-1">{{ __('best') }}: {{ $deal->buyerMatches->max('match_score') }}%</span>
                            @else
                                <span class="text-secondary">{{ __('None') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Contract Price') }}</div>
                        <div class="datagrid-content">{{ Fmt::currency($deal->contract_price) }}</div>
                    </div>
                    @if($businessMode === 'wholesale')
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Assignment Fee') }}</div>
                        <div class="datagrid-content">{{ Fmt::currency($deal->assignment_fee) }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Earnest Money') }}</div>
                        <div class="datagrid-content">{{ Fmt::currency($deal->earnest_money) }}</div>
                    </div>
                    @else
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Listing Commission') }}</div>
                        <div class="datagrid-content">{{ $deal->listing_commission_pct ? $deal->listing_commission_pct . '%' : '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Buyer Commission') }}</div>
                        <div class="datagrid-content">{{ $deal->buyer_commission_pct ? $deal->buyer_commission_pct . '%' : '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Total Commission') }}</div>
                        <div class="datagrid-content">{{ Fmt::currency($deal->total_commission) }}</div>
                    </div>
                    @if($deal->mls_number)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('MLS #') }}</div>
                        <div class="datagrid-content">{{ $deal->mls_number }}</div>
                    </div>
                    @endif
                    @endif
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Contract Date') }}</div>
                        <div class="datagrid-content">{{ $deal->contract_date ? Fmt::date($deal->contract_date) : '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Closing Date') }}</div>
                        <div class="datagrid-content">{{ $deal->closing_date ? $deal->closing_date->format('M d, Y') : '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Inspection Period') }}</div>
                        <div class="datagrid-content">{{ $deal->inspection_period_days ? $deal->inspection_period_days . ' ' . __('days') : '-' }}</div>
                    </div>
                </div>

                @if($deal->stage === 'under_contract' && $deal->due_diligence_end_date)
                <div class="mt-3">
                    <div class="alert {{ $deal->is_due_diligence_urgent ? 'alert-danger' : 'alert-info' }}">
                        <strong>{{ __('Due Diligence:') }}</strong>
                        {{ __('Ends') }} {{ $deal->due_diligence_end_date->format('M d, Y') }}
                        @if($deal->due_diligence_days_remaining !== null)
                            ({{ $deal->due_diligence_days_remaining }} {{ __('days remaining') }})
                        @endif
                    </div>
                </div>
                @endif

                @if($deal->notes)
                <div class="mt-3">
                    <h4>{{ __('Notes') }}</h4>
                    <p>{{ $deal->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Buyer Matches -->
        @if($deal->buyerMatches->count())
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Matched') }} {{ $modeTerms['buyer_label'] ?? __('Buyers') }}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Client') : __('Buyer') }}</th>
                            <th>{{ __('Score') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deal->buyerMatches->sortByDesc('match_score') as $match)
                        <tr>
                            <td>
                                <a href="{{ route('buyers.show', $match->buyer) }}">{{ $match->buyer->company_name }}</a>
                                <div class="text-secondary small">{{ $match->buyer->contact_name }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $match->match_score >= 70 ? 'bg-green-lt' : ($match->match_score >= 40 ? 'bg-yellow-lt' : 'bg-red-lt') }}">
                                    {{ $match->match_score }}%
                                </span>
                            </td>
                            <td>{{ $match->buyer->phone ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $match->status === 'interested' ? 'green' : ($match->status === 'contacted' ? 'blue' : ($match->status === 'passed' ? 'red' : 'secondary')) }}-lt">
                                    {{ __(ucfirst($match->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('buyers.show', $match->buyer) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                @if(!$match->notified_at)
                                <form method="POST" action="{{ route('deals.notifyBuyer', [$deal, $match]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Notify') }}</button>
                                </form>
                                @else
                                <span class="badge bg-green-lt">{{ __('Notified') }} {{ $match->notified_at->diffForHumans() }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Documents -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Documents') }}</h3>
                <div class="card-actions">
                    <a href="{{ route('documents.generate', $deal) }}" class="btn btn-sm btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 11v6"/><path d="M9 14l3 -3l3 3"/></svg>
                        {{ __('Generate Document') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('deals.uploadDocument', $deal) }}" method="POST" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        <button type="submit" class="btn btn-outline-primary">{{ __('Upload') }}</button>
                    </div>
                    <small class="text-secondary">{{ __('PDF, JPG, PNG. Max 10MB.') }}</small>
                </form>
                @if($deal->documents->count())
                <div class="list-group list-group-flush">
                    @foreach($deal->documents as $doc)
                    <a href="{{ route('deals.downloadDocument', $doc) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        {{ $doc->original_name }}
                        <small class="text-secondary">{{ number_format($doc->size / 1024, 1) }} KB</small>
                    </a>
                    @endforeach
                </div>
                @else
                <p class="text-secondary">{{ __('No documents uploaded.') }}</p>
                @endif

                {{-- Generated Documents --}}
                @php $genDocs = \App\Models\GeneratedDocument::where('deal_id', $deal->id)->with('template')->latest()->get(); @endphp
                @if($genDocs->count())
                <hr class="my-3">
                <h4 class="mb-2">{{ __('Generated Documents') }}</h4>
                <div class="list-group list-group-flush">
                    @foreach($genDocs as $genDoc)
                    <a href="{{ route('documents.show', $genDoc) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            {{ $genDoc->name }}
                            @if($genDoc->template)
                                <span class="badge bg-secondary-lt ms-1">{{ \App\Models\DocumentTemplate::typeLabel($genDoc->template->type) }}</span>
                            @endif
                        </div>
                        <small class="text-secondary">{{ $genDoc->created_at->diffForHumans() }}</small>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Activity Log -->
        <div class="card mb-3" id="activity-section">
            <div class="card-header">
                <h3 class="card-title">{{ __('Activity Log') }}</h3>
            </div>
            <div class="card-body">
                <!-- Log Activity Form -->
                <form action="{{ route('deals.activities.store', $deal) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-auto">
                            <select name="type" class="form-select form-select-sm" required>
                                <option value="note">{{ __('Note') }}</option>
                                <option value="call">{{ __('Call') }}</option>
                                <option value="email">{{ __('Email') }}</option>
                                <option value="meeting">{{ __('Meeting') }}</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <input type="text" name="subject" class="form-control form-control-sm" placeholder="{{ __('Subject (optional)') }}">
                        </div>
                        <div class="col">
                            <input type="text" name="body" class="form-control form-control-sm" placeholder="{{ __('Notes...') }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Log') }}</button>
                        </div>
                    </div>
                </form>

                @if($deal->activities->count())
                <div class="list-group list-group-flush">
                    @foreach($deal->activities->sortByDesc('logged_at') as $activity)
                    <div class="list-group-item px-0">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                @php
                                    $actColors = ['call'=>'bg-green-lt','sms'=>'bg-blue-lt','email'=>'bg-yellow-lt','note'=>'bg-secondary-lt','meeting'=>'bg-purple-lt','stage_change'=>'bg-cyan-lt'];
                                @endphp
                                <span class="avatar avatar-sm {{ $actColors[$activity->type] ?? 'bg-secondary-lt' }}">
                                    {{ strtoupper(substr($activity->type, 0, 1)) }}
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-truncate">
                                    <strong>{{ __(ucwords(str_replace('_', ' ', $activity->type))) }}</strong>
                                    @if($activity->subject) - {{ $activity->subject }} @endif
                                </div>
                                @if($activity->body)
                                <div class="text-secondary small">{{ $activity->body }}</div>
                                @endif
                                <div class="text-secondary small">
                                    {{ $activity->agent->name ?? '' }} &middot; {{ $activity->logged_at ? $activity->logged_at->diffForHumans() : $activity->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-secondary">{{ __('No activities logged yet.') }}</p>
                @endif
            </div>
        </div>
        @if(($businessMode ?? 'wholesale') === 'realestate')
            @include('deals._transaction_checklist', ['deal' => $deal])
            @include('deals._offers', ['deal' => $deal])
        @endif
    </div>

    <div class="col-md-4">
        <!-- Lead & Property Context -->
        @if($deal->lead)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
                    {{ __('Lead') }}
                </h3>
                <div class="card-actions">
                    <a href="{{ route('leads.show', $deal->lead) }}" class="btn btn-outline-primary btn-sm">
                        {{ __('Open Lead') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm ms-1" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11 7h-5a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-5"/><line x1="10" y1="14" x2="20" y2="4"/><polyline points="15 4 20 4 20 9"/></svg>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-md bg-primary-lt me-3">{{ strtoupper(substr($deal->lead->first_name, 0, 1)) }}{{ strtoupper(substr($deal->lead->last_name, 0, 1)) }}</span>
                    <div>
                        <div class="fw-bold">{{ $deal->lead->full_name }}</div>
                        <div class="d-flex gap-2 mt-1">
                            @php $tempColors = ['hot' => 'bg-red-lt', 'warm' => 'bg-yellow-lt', 'cold' => 'bg-azure-lt']; @endphp
                            <span class="badge {{ $tempColors[$deal->lead->temperature] ?? 'bg-secondary-lt' }}">{{ __(ucfirst($deal->lead->temperature)) }}</span>
                            <span class="badge bg-green-lt">{{ __(ucwords(str_replace('_', ' ', $deal->lead->status))) }}</span>
                        </div>
                    </div>
                </div>
                <div class="datagrid" style="--tblr-datagrid-item-width: 100%;">
                    @if($deal->lead->phone)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Phone') }}</div>
                        <div class="datagrid-content">
                            <a href="tel:{{ $deal->lead->phone }}" class="text-reset">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/></svg>
                                {{ $deal->lead->phone }}
                            </a>
                        </div>
                    </div>
                    @endif
                    @if($deal->lead->email)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Email') }}</div>
                        <div class="datagrid-content">
                            <a href="mailto:{{ $deal->lead->email }}" class="text-reset">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
                                {{ $deal->lead->email }}
                            </a>
                        </div>
                    </div>
                    @endif
                    @if($deal->lead->property)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Property') }}</div>
                        <div class="datagrid-content">{{ $deal->lead->property->address }}@if($deal->lead->property->city), {{ $deal->lead->property->city }}@endif</div>
                    </div>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    @if($deal->lead->property->after_repair_value)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('ARV') }}</div>
                        <div class="datagrid-content">{{ Fmt::currency($deal->lead->property->after_repair_value) }}</div>
                    </div>
                    @endif
                    @if($deal->lead->property->repair_estimate)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Repairs') }}</div>
                        <div class="datagrid-content">{{ Fmt::currency($deal->lead->property->repair_estimate) }}</div>
                    </div>
                    @endif
                    @else
                    @if($deal->lead->property->list_price)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('List Price') }}</div>
                        <div class="datagrid-content">{{ Fmt::currency($deal->lead->property->list_price) }}</div>
                    </div>
                    @endif
                    @if($deal->lead->property->listing_status)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Listing Status') }}</div>
                        <div class="datagrid-content">
                            @php $listingStatusColors = ['active' => 'bg-green-lt', 'pending' => 'bg-yellow-lt', 'sold' => 'bg-purple-lt', 'withdrawn' => 'bg-red-lt', 'expired' => 'bg-secondary-lt']; @endphp
                            <span class="badge {{ $listingStatusColors[$deal->lead->property->listing_status] ?? 'bg-blue-lt' }}">{{ __(ucfirst($deal->lead->property->listing_status)) }}</span>
                        </div>
                    </div>
                    @endif
                    @endif
                    @endif
                    @if(($businessMode ?? 'wholesale') === 'wholesale' && $deal->lead->motivation_score)
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Motivation') }}</div>
                        <div class="datagrid-content">
                            @php $ms = $deal->lead->motivation_score; @endphp
                            <span class="badge {{ $ms >= 70 ? 'bg-green-lt' : ($ms >= 40 ? 'bg-yellow-lt' : 'bg-secondary-lt') }}">{{ $ms }}/100</span>
                            @if($deal->lead->ai_motivation_score !== null)
                            <span class="badge bg-purple-lt ms-1" title="{{ __('AI Score') }}">{{ $deal->lead->ai_motivation_score }}/100</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @if($deal->lead->do_not_contact)
                <div class="alert alert-danger mt-2 mb-0 py-1 px-2">
                    <small class="fw-bold">{{ __('Do Not Contact') }}</small>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Stage Management -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Change Stage') }}</h3>
            </div>
            <div class="card-body">
                <form id="stage-form">
                    <select name="stage" class="form-select mb-2" id="stage-select">
                        @foreach(\App\Models\Deal::stageLabels() as $key => $label)
                            <option value="{{ $key }}" {{ $deal->stage === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary w-100">{{ __('Update Stage') }}</button>
                </form>
            </div>
        </div>

        <!-- Edit Deal -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Edit Transaction') : __('Edit Deal') }}</h3>
            </div>
            <div class="card-body">
                <form id="deal-edit-form">
                    <div class="mb-2">
                        <label class="form-label">{{ __('Contract Price ($)') }}</label>
                        <input type="number" name="contract_price" class="form-control form-control-sm" step="0.01" value="{{ $deal->contract_price }}">
                    </div>
                    @if($businessMode === 'wholesale')
                    <div class="mb-2">
                        <label class="form-label">{{ __('Assignment Fee ($)') }}</label>
                        <input type="number" name="assignment_fee" class="form-control form-control-sm" step="0.01" value="{{ $deal->assignment_fee }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Earnest Money ($)') }}</label>
                        <input type="number" name="earnest_money" class="form-control form-control-sm" step="0.01" value="{{ $deal->earnest_money }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Inspection Period (days)') }}</label>
                        <input type="number" name="inspection_period_days" class="form-control form-control-sm" min="0" value="{{ $deal->inspection_period_days }}">
                    </div>
                    @else
                    <div class="mb-2">
                        <label class="form-label">{{ __('Listing Commission (%)') }}</label>
                        <input type="number" name="listing_commission_pct" class="form-control form-control-sm" step="0.01" min="0" max="100" value="{{ $deal->listing_commission_pct }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Buyer Commission (%)') }}</label>
                        <input type="number" name="buyer_commission_pct" class="form-control form-control-sm" step="0.01" min="0" max="100" value="{{ $deal->buyer_commission_pct }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Total Commission ($)') }}</label>
                        <input type="number" name="total_commission" class="form-control form-control-sm" step="0.01" value="{{ $deal->total_commission }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('MLS #') }}</label>
                        <input type="text" name="mls_number" class="form-control form-control-sm" maxlength="30" value="{{ $deal->mls_number }}">
                    </div>
                    @endif
                    <div class="mb-2">
                        <label class="form-label">{{ __('Contract Date') }}</label>
                        <input type="date" name="contract_date" class="form-control form-control-sm" value="{{ $deal->contract_date ? $deal->contract_date->format('Y-m-d') : '' }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Closing Date') }}</label>
                        <input type="date" name="closing_date" class="form-control form-control-sm" value="{{ $deal->closing_date ? $deal->closing_date->format('Y-m-d') : '' }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="3">{{ $deal->notes }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-sm">{{ __('Save Changes') }}</button>
                </form>
            </div>
        </div>

        @if($businessMode === 'realestate')
        @include('deals._commission_calculator', ['deal' => $deal])
        @endif
    </div>
</div>

@if(auth()->user()->tenant->ai_enabled)
<!-- AI Result Modal -->
<div class="modal modal-blur fade" id="deal-ai-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deal-ai-modal-title">{{ __('AI Assistant') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="deal-ai-loading" class="text-center py-4">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('AI is thinking...') }}</p>
                </div>
                <div id="deal-ai-result" style="display: none;">
                    <div style="line-height: 1.6;" id="deal-ai-text"></div>
                </div>
                <div id="deal-ai-error" class="alert alert-danger" style="display: none;"></div>
                {{-- AI recommended actions --}}
                <div id="deal-ai-actions" class="mt-3" style="display:none;">
                    <h4 class="mb-2">{{ __('Recommended Actions') }}</h4>
                    <div id="deal-ai-actions-list"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="deal-ai-copy-btn" style="display: none;">{{ __('Copy to Clipboard') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
document.getElementById('stage-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const stage = document.getElementById('stage-select').value;
    fetch('{{ url("/pipeline") }}/{{ $deal->id }}/stage', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ stage: stage })
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
});

document.getElementById('deal-edit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = {};
    formData.forEach((val, key) => data[key] = val);

    fetch('{{ url("/pipeline") }}/{{ $deal->id }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify(data)
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
});

// Track recently viewed
if (window.trackRecentlyViewed) {
    window.trackRecentlyViewed('deal', {{ $deal->id }}, @json($deal->title ?? 'Deal #'.$deal->id), '{{ route("deals.show", $deal) }}');
}

@if(auth()->user()->tenant->ai_enabled)
// ── Auto-load AI Briefing ───────────────────────
(function() {
    var briefingText = document.getElementById('deal-briefing-text');
    var briefingLoading = document.getElementById('deal-briefing-loading');
    var briefingError = document.getElementById('deal-briefing-error');
    var briefingRefresh = document.getElementById('deal-briefing-refresh');
    var briefingLinks = document.getElementById('deal-briefing-links');
    if (!briefingText) return;
    var csrfTkn = document.querySelector('meta[name="csrf-token"]').content;
    var fmtCur = function(v) { return v ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v) : ''; };

    var typeLabels = { deal: '{{ ($businessMode ?? "wholesale") === "realestate" ? __("Transaction") : __("Deal") }}', lead: '{{ __("Lead") }}', buyer: '{{ ($businessMode ?? "wholesale") === "realestate" ? __("Client") : __("Buyer") }}', property: '{{ __("Property") }}' };
    var typeColors = { deal: 'bg-blue-lt', lead: 'bg-green-lt', buyer: 'bg-orange-lt', property: 'bg-cyan-lt' };
    var isRealEstate = {{ ($businessMode ?? 'wholesale') === 'realestate' ? 'true' : 'false' }};
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
            if (link.temp) text += ' (' + link.temp + ')';
            if (!isRealEstate && link.arv) text += ' — ARV ' + fmtCur(link.arv);
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
        var url = '{{ url("/ai/deal-briefing") }}' + (force ? '?refresh=1' : '');
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfTkn, 'Accept': 'application/json' },
            body: JSON.stringify({ deal_id: {{ $deal->id }} })
        }).then(function(r) { return r.json(); }).then(function(res) {
            briefingLoading.style.display = 'none';
            if (res.error === 'disabled') {
                document.getElementById('deal-briefing-card').style.display = 'none'; return;
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

// ── AI Functions for Deal Show ───────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var dealAiModalEl = document.getElementById('deal-ai-modal');
    var dealAiModal = new bootstrap.Modal(dealAiModalEl);
    dealAiModalEl.addEventListener('hide.bs.modal', function() {
        if (dealAiModalEl.contains(document.activeElement)) document.activeElement.blur();
    });
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var dealId = {{ $deal->id }};
    var lastAiText = '';
    var lastRequestType = '';

    function showDealAiModal(title) {
        document.getElementById('deal-ai-modal-title').textContent = title;
        document.getElementById('deal-ai-loading').style.display = 'block';
        document.getElementById('deal-ai-result').style.display = 'none';
        document.getElementById('deal-ai-error').style.display = 'none';
        document.getElementById('deal-ai-copy-btn').style.display = 'none';
        document.getElementById('deal-ai-actions').style.display = 'none';
        document.getElementById('deal-ai-actions-list').innerHTML = '';
        dealAiModal.show();
    }

    var stageLabels = @json(\App\Models\Deal::stageLabels());
    var priorityLabels = { low: '{{ __('Low') }}', medium: '{{ __('Medium') }}', high: '{{ __('High') }}' };
    var priorityColors = { low: 'secondary', medium: 'warning', high: 'danger' };

    function showDealAiResult(text, actions) {
        lastAiText = text;
        document.getElementById('deal-ai-loading').style.display = 'none';
        document.getElementById('deal-ai-result').style.display = 'block';
        document.getElementById('deal-ai-text').innerHTML = window.renderAiMarkdown(text);
        document.getElementById('deal-ai-copy-btn').style.display = 'inline-block';
        renderActions(actions || []);
    }

    function renderActions(actions) {
        var container = document.getElementById('deal-ai-actions-list');
        var wrapper = document.getElementById('deal-ai-actions');
        container.innerHTML = '';
        if (!actions.length) { wrapper.style.display = 'none'; return; }
        wrapper.style.display = 'block';

        actions.forEach(function(action, idx) {
            var row = document.createElement('div');
            row.className = 'd-flex align-items-center justify-content-between border rounded p-2 mb-2';
            var left = document.createElement('div');
            left.className = 'd-flex align-items-center gap-2';

            var icon = '';
            var detail = '';
            if (action.type === 'stage_change') {
                icon = '<span class="badge bg-blue-lt">{{ __('Stage') }}</span>';
                detail = '<span>' + action.label + ' &rarr; <strong>' + (stageLabels[action.stage] || action.stage) + '</strong></span>';
            } else if (action.type === 'create_task') {
                icon = '<span class="badge bg-yellow-lt">{{ __('Task') }}</span>';
                var pColor = priorityColors[action.priority] || 'secondary';
                detail = '<span>' + action.label + ' <span class="badge bg-' + pColor + '-lt ms-1">' + (priorityLabels[action.priority] || action.priority) + '</span> <span class="text-secondary small">(' + action.due_days + 'd)</span></span>';
            } else if (action.type === 'add_note') {
                icon = '<span class="badge bg-cyan-lt">{{ __('Note') }}</span>';
                detail = '<span>' + action.label + '</span>';
            }
            left.innerHTML = icon + detail;

            var btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-outline-success ms-2';
            btn.style.whiteSpace = 'nowrap';
            btn.textContent = '{{ __('Apply') }}';
            btn.setAttribute('data-action-idx', idx);
            btn.addEventListener('click', function() { applyAction(action, this); });

            row.appendChild(left);
            row.appendChild(btn);
            container.appendChild(row);
        });
    }

    function applyAction(action, btn) {
        btn.disabled = true;
        btn.textContent = '{{ __('Applying...') }}';

        if (action.type === 'stage_change') {
            fetch('{{ url("/pipeline") }}/{{ $deal->id }}/stage', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ stage: action.stage })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) { markApplied(btn); setTimeout(function() { location.reload(); }, 800); }
                else { markFailed(btn); }
            }).catch(function() { markFailed(btn); });

        } else if (action.type === 'create_task') {
            var dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + (action.due_days || 3));
            var dueDateStr = dueDate.toISOString().split('T')[0];
            fetch('{{ url("/leads/" . $deal->lead_id . "/tasks") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ title: action.title || action.label, due_date: dueDateStr })
            }).then(function(r) {
                if (r.redirected || !r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function(data) {
                if (data.success) { markApplied(btn, '{{ __('Task Created') }}'); }
                else { console.error('Task create response:', data); markFailed(btn); }
            }).catch(function(e) { console.error('Task create error:', e); markFailed(btn); });

        } else if (action.type === 'add_note') {
            fetch('{{ url("/pipeline/" . $deal->id . "/activities") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ type: 'note', subject: '{{ __('AI Recommendation') }}', body: action.text || action.label })
            }).then(function(r) {
                if (r.redirected || !r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function(data) {
                if (data.success || data.id) { markApplied(btn, '{{ __('Note Saved') }}'); }
                else { console.error('Note save response:', data); markFailed(btn); }
            }).catch(function(e) { console.error('Note save error:', e); markFailed(btn); });
        }
    }

    function markApplied(btn, label) {
        btn.textContent = label || '{{ __('Applied') }}';
        btn.className = 'btn btn-sm btn-success ms-2 disabled';
        btn.disabled = true;
    }
    function markFailed(btn) {
        btn.textContent = '{{ __('Failed') }}';
        btn.className = 'btn btn-sm btn-outline-danger ms-2';
        setTimeout(function() { btn.textContent = '{{ __('Apply') }}'; btn.className = 'btn btn-sm btn-outline-success ms-2'; btn.disabled = false; }, 2000);
    }

    function showDealAiError(msg) {
        document.getElementById('deal-ai-loading').style.display = 'none';
        document.getElementById('deal-ai-error').style.display = 'block';
        document.getElementById('deal-ai-error').textContent = msg;
    }

    function dealAiRequest(url, data, title) {
        showDealAiModal(title);
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.error) {
                showDealAiError(res.error);
                return;
            }
            var text = res.analysis || res.advice || res.message || '';
            showDealAiResult(text, res.actions || []);
        }).catch(function() {
            showDealAiError('{{ __('Request failed. Please try again.') }}');
        });
    }

    // AI Analysis button
    document.getElementById('ai-analyze-btn').addEventListener('click', function() {
        lastRequestType = 'analysis';
        dealAiRequest('{{ url("/ai/analyze-deal") }}', { deal_id: dealId }, '{{ __('AI Deal Analysis') }}');
    });

    // AI Stage Advice button
    document.getElementById('ai-stage-advice-btn').addEventListener('click', function() {
        lastRequestType = 'stage-advice';
        dealAiRequest('{{ url("/ai/deal-stage-advice") }}', { deal_id: dealId }, '{{ __('AI Stage Advice') }}');
    });

    // Copy to Clipboard button
    document.getElementById('deal-ai-copy-btn').addEventListener('click', function() {
        var btn = this;
        navigator.clipboard.writeText(lastAiText).then(function() {
            btn.textContent = '{{ __('Copied!') }}';
            setTimeout(function() { btn.textContent = '{{ __('Copy to Clipboard') }}'; }, 2000);
        });
    });
}); // end DOMContentLoaded
@endif
</script>
@endpush
@if(($businessMode ?? 'wholesale') === 'realestate' && auth()->user()->tenant->ai_enabled)
@include('deals._marketing_kit_modal')
@push('scripts')
<script>
document.getElementById('marketing-kit-btn')?.addEventListener('click', function() {
    var modal = new bootstrap.Modal(document.getElementById('marketing-kit-modal'));
    modal.show();
    document.getElementById('marketing-kit-loading').style.display = 'block';
    document.getElementById('marketing-kit-content').style.display = 'none';
    document.getElementById('marketing-kit-footer').style.display = 'none';
    document.getElementById('marketing-kit-error').style.display = 'none';

    fetch('{{ url("/ai/marketing-kit") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ deal_id: {{ $deal->id }} })
    }).then(function(r) { return r.json(); }).then(function(res) {
        document.getElementById('marketing-kit-loading').style.display = 'none';
        if (res.error) {
            document.getElementById('marketing-kit-error').textContent = res.error;
            document.getElementById('marketing-kit-error').style.display = 'block';
        } else {
            ['property_description', 'social_caption', 'flyer_copy', 'open_house_blurb', 'email_blast'].forEach(function(key) {
                document.getElementById('mk-' + key).textContent = res[key] || '';
            });
            document.getElementById('marketing-kit-content').style.display = 'block';
            document.getElementById('marketing-kit-footer').style.display = '';
        }
    }).catch(function() {
        document.getElementById('marketing-kit-loading').style.display = 'none';
        document.getElementById('marketing-kit-error').textContent = '{{ __("Request failed.") }}';
        document.getElementById('marketing-kit-error').style.display = 'block';
    });
});

document.querySelectorAll('.copy-section-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var text = document.getElementById('mk-' + this.dataset.section).textContent;
        navigator.clipboard.writeText(text);
    });
});
document.getElementById('mk-copy-all')?.addEventListener('click', function() {
    var text = '';
    ['property_description', 'social_caption', 'flyer_copy', 'open_house_blurb', 'email_blast'].forEach(function(key) {
        text += key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); }) + ':\n' + document.getElementById('mk-' + key).textContent + '\n\n';
    });
    navigator.clipboard.writeText(text);
});
document.getElementById('mk-export-text')?.addEventListener('click', function() {
    var text = '';
    ['property_description', 'social_caption', 'flyer_copy', 'open_house_blurb', 'email_blast'].forEach(function(key) {
        text += key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); }) + ':\n' + document.getElementById('mk-' + key).textContent + '\n\n';
    });
    var blob = new Blob([text], { type: 'text/plain' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'marketing-kit.txt';
    a.click();
});
</script>
@endpush
@endif
@endsection
