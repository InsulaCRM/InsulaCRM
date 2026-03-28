@extends('layouts.app')

@section('title', $lead->full_name)
@section('page-title', $lead->full_name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('leads.index') }}">{{ __('Leads') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $lead->full_name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- AI Briefing (auto-loads) -->
        @if(auth()->user()->tenant->ai_enabled && auth()->user()->tenant->ai_briefings_enabled)
        <div class="card mb-3" id="ai-briefing-card" style="border-left: 3px solid #ae3ec9; background: linear-gradient(135deg, rgba(174,62,201,0.03) 0%, rgba(174,62,201,0.07) 100%);">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm bg-purple text-white me-2 flex-shrink-0" style="width: 28px; height: 28px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        </span>
                        <div>
                            <div class="fw-bold" style="font-size: 0.85rem; line-height: 1.2;">{{ __('AI Briefing') }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">{{ __('Auto-generated summary') }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost-purple btn-sm px-2" id="ai-briefing-refresh" title="{{ __('Refresh') }}" style="display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                        <span style="font-size: 0.7rem;">{{ __('Refresh') }}</span>
                    </button>
                </div>
                <div id="ai-briefing-loading" style="font-size: 0.82rem;">
                    <span class="spinner-border spinner-border-sm text-purple me-1" style="width: 0.75rem; height: 0.75rem;"></span>
                    <span class="text-secondary">{{ __('Generating lead briefing...') }}</span>
                </div>
                <div id="ai-briefing-text" style="font-size: 0.82rem; line-height: 1.6; display: none; color: #334155;"></div>
                <div id="ai-briefing-links" style="display: none;" class="mt-2 pt-2 d-flex flex-wrap gap-1" data-has-border="1"></div>
                <div id="ai-briefing-error" style="font-size: 0.82rem; display: none;" class="text-danger"></div>
            </div>
        </div>
        @endif

        <!-- Lead Info Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Contact Details') : __('Lead Information') }}</h3>
                <div class="card-actions">
                    <a href="{{ route('leads.edit', $lead) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Lead ID') }}</div>
                        <div class="datagrid-content"><code>#{{ $lead->id }}</code></div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Name') }}</div>
                        <div class="datagrid-content">{{ $lead->full_name }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Phone') }}</div>
                        <div class="datagrid-content">
                            @if($lead->phone)
                                <a href="tel:{{ $lead->phone }}" class="text-reset">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/></svg>
                                    {{ $lead->phone }}
                                </a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Email') }}</div>
                        <div class="datagrid-content">
                            @if($lead->email)
                                <a href="#" class="text-reset" data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
                                    {{ $lead->email }}
                                </a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Source') }}</div>
                        <div class="datagrid-content">
                            @php
                                $sourceColors = [
                                    'referral' => 'bg-green-lt', 'open_house' => 'bg-teal-lt', 'sign_call' => 'bg-orange-lt',
                                    'zillow' => 'bg-blue-lt', 'realtor_com' => 'bg-red-lt', 'sphere' => 'bg-cyan-lt',
                                    'past_client' => 'bg-purple-lt', 'social_media' => 'bg-pink-lt', 'website' => 'bg-indigo-lt',
                                    'driving_for_dollars' => 'bg-orange-lt', 'direct_mail' => 'bg-green-lt',
                                    'cold_calling' => 'bg-cyan-lt', 'bandit_sign' => 'bg-yellow-lt',
                                    'mls' => 'bg-red-lt', 'auction' => 'bg-purple-lt',
                                ];
                            @endphp
                            <span class="badge {{ $sourceColors[$lead->lead_source] ?? 'bg-blue-lt' }}">{{ __(ucwords(str_replace('_', ' ', $lead->lead_source))) }}</span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Status') }}</div>
                        <div class="datagrid-content">
                            <span class="badge bg-green-lt">{{ __(ucwords(str_replace('_', ' ', $lead->status))) }}</span>
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Temperature') }}</div>
                        <div class="datagrid-content">
                            @php $tempColors = ['hot' => 'bg-red-lt', 'warm' => 'bg-yellow-lt', 'cold' => 'bg-azure-lt']; @endphp
                            <span class="badge {{ $tempColors[$lead->temperature] ?? 'bg-secondary-lt' }}">@if($lead->temperature === 'hot')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2-2.96 0-7-1-8 0 3.038-1.773 4.741-3 6-1.226 1.26-2 3.24-2 5a6 6 0 1 0 12 0c0-1.532-1.056-3.94-2-5-1.786 3-2.791 3-4 2z"/></svg>@elseif($lead->temperature === 'warm')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8-9v1m8 8h1m-9 8v1m-6.4-15.4l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>@elseif($lead->temperature === 'cold')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 4l2 1l2-1"/><path d="M12 2v6.5l3 1.72"/><path d="M17.928 6.268l.134 2.232l1.866 1.232"/><path d="M20.66 7l-5.629 3.25l.01 3.458"/><path d="M19.928 14.268l-1.866 1.232l-.134 2.232"/><path d="M20.66 17l-5.629-3.25l-2.99 1.738"/><path d="M14 20l-2-1l-2 1"/><path d="M12 22v-6.5l-3-1.72"/><path d="M6.072 17.732l-.134-2.232l-1.866-1.232"/><path d="M3.34 17l5.629-3.25l-.01-3.458"/><path d="M4.072 9.732l1.866-1.232l.134-2.232"/><path d="M3.34 7l5.629 3.25l2.99-1.738"/></svg>@endif {{ __(ucfirst($lead->temperature)) }}</span>
                            @if(($businessMode ?? 'wholesale') === 'realestate' && $lead->contact_type)
                            <span class="badge bg-cyan-lt">{{ __(ucwords(str_replace('_', ' ', $lead->contact_type))) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Assigned Agent') }}</div>
                        <div class="datagrid-content">{{ $lead->agent->name ?? '-' }}</div>
                    </div>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Motivation Score') }}</div>
                        <div class="datagrid-content">
                            @php $ms = $lead->motivation_score ?? 0; @endphp
                            <span class="badge {{ $ms >= 70 ? 'bg-green-lt' : ($ms >= 40 ? 'bg-yellow-lt' : 'bg-secondary-lt') }}" title="{{ __('Automated score from lead data, list stacking, and engagement') }}">
                                {{ $ms }}/100
                            </span>
                            @if($lead->ai_motivation_score !== null)
                                @php $ai = $lead->ai_motivation_score; @endphp
                                <span class="badge {{ $ai >= 70 ? 'bg-purple-lt' : ($ai >= 40 ? 'bg-purple-lt' : 'bg-purple-lt') }} ms-1" title="{{ __('AI assessment score') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                    {{ $ai }}/100
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('DNC') }}</div>
                        <div class="datagrid-content">
                            @if($lead->do_not_contact)
                                <span class="badge bg-red-lt">{{ __('Do Not Contact') }}</span>
                            @else
                                <span class="badge bg-green-lt">{{ __('OK to Contact') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Lists') }}</div>
                        <div class="datagrid-content">
                            @if($lead->lists->count())
                                @foreach($lead->lists as $list)
                                    <a href="{{ route('lists.show', $list) }}" class="badge bg-cyan-lt text-decoration-none me-1">{{ $list->name }}</a>
                                @endforeach
                            @else
                                <span class="text-secondary">{{ __('None') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Created') }}</div>
                        <div class="datagrid-content">{{ $lead->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
                @if($lead->notes)
                <div class="mt-3">
                    <h4>{{ __('Notes') }}</h4>
                    <p>{{ $lead->notes }}</p>
                </div>
                @endif

                @php
                    $customFieldDefs = \App\Models\CustomFieldDefinition::forEntity('lead');
                    $cfValues = $lead->custom_fields ?? [];
                @endphp
                @if($customFieldDefs->count() && !empty($cfValues))
                <div class="mt-3">
                    <h4>{{ __('Additional Information') }}</h4>
                    <div class="datagrid">
                        @foreach($customFieldDefs as $cfd)
                            @if(isset($cfValues[$cfd->slug]) && $cfValues[$cfd->slug] !== '' && $cfValues[$cfd->slug] !== '0')
                            <div class="datagrid-item">
                                <div class="datagrid-title">{{ __($cfd->name) }}</div>
                                <div class="datagrid-content">
                                    @if($cfd->field_type === 'checkbox')
                                        {{ $cfValues[$cfd->slug] ? __('Yes') : __('No') }}
                                    @else
                                        {{ $cfValues[$cfd->slug] }}
                                    @endif
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Property Section -->
        <div id="property-details"></div>
        @include('leads._property_form')

        <!-- Photos Section -->
        @include('leads._photos')

        <!-- Log Activity Form -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Log Activity') }}</h3>
                <div class="card-actions">
                    <a class="btn btn-ghost-secondary btn-sm" data-bs-toggle="collapse" href="#section-log-activity" aria-expanded="true" aria-label="{{ __('Toggle section') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="6 9 12 15 18 9"/></svg>
                    </a>
                </div>
            </div>
            <div class="card-body collapse show" id="section-log-activity">
                {{-- Quick-log buttons --}}
                @unless($lead->do_not_contact)
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-green quick-log-btn" data-type="call">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/></svg>
                        {{ __('Log Call') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-yellow quick-log-btn" data-type="email">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
                        {{ __('Log Email') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-log-btn" data-type="note">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="13" y1="20" x2="20" y2="13"/><path d="M13 20v-6a1 1 0 0 1 1 -1h6v-7a2 2 0 0 0 -2 -2h-12a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7"/></svg>
                        {{ __('Add Note') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-purple quick-log-btn" data-type="meeting">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                        {{ __('Log Meeting') }}
                    </button>
                </div>
                @endunless
                @if($lead->do_not_contact)
                <div class="alert alert-danger mb-3">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="5.7" y1="5.7" x2="18.3" y2="18.3"/></svg>
                        <div>
                            <strong>{{ __('This lead is on the Do Not Contact list.') }}</strong> {{ __('Outreach activities are blocked.') }}
                        </div>
                    </div>
                </div>
                @endif
                <form action="{{ route('leads.activities.store', $lead) }}" method="POST">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <select name="type" class="form-select" required id="activity-type-select">
                                @php $outreach = \App\Services\CustomFieldService::$outreachActivityTypes; @endphp
                                @foreach(\App\Services\CustomFieldService::getOptions('activity_type') as $val => $label)
                                    @if($lead->do_not_contact && in_array($val, $outreach))
                                        @continue
                                    @endif
                                    <option value="{{ $val }}" {{ $lead->do_not_contact && $val === 'note' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="subject" class="form-control" placeholder="{{ __('Subject (optional)') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <textarea name="body" class="form-control" rows="2" placeholder="{{ __('Notes...') }}"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Log Activity') }}</button>
                        @if(auth()->user()->tenant->ai_enabled)
                        <button type="button" class="btn btn-outline-purple btn-sm" id="ai-draft-btn" title="{{ __('AI Draft Follow-Up') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                            {{ __('AI Draft') }}
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card mb-3" id="activity-section">
            <div class="card-header">
                <h3 class="card-title">{{ __('Activity Log') }}</h3>
                <div class="card-actions">
                    <a class="btn btn-ghost-secondary btn-sm" data-bs-toggle="collapse" href="#section-timeline" aria-expanded="true" aria-label="{{ __('Toggle section') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="6 9 12 15 18 9"/></svg>
                    </a>
                </div>
            </div>
            <div class="card-body collapse show" id="section-timeline">
                @if($lead->activities->count())
                <div class="list-group list-group-flush">
                    @foreach($lead->activities->sortByDesc('logged_at') as $activity)
                    <div class="list-group-item" id="activity-{{ $activity->id }}">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                @php
                                    $icons = [
                                        'call' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-phone" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/></svg>',
                                        'sms' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-message" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 21v-13a3 3 0 0 1 3 -3h10a3 3 0 0 1 3 3v6a3 3 0 0 1 -3 3h-9l-4 4"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="14" y2="13"/></svg>',
                                        'email' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mail" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>',
                                        'voicemail' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-phone-incoming" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/><path d="M15 9l5 -5"/><path d="M15 5l0 4l4 0"/></svg>',
                                        'direct_mail' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mailbox" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 21v-6.5a3.5 3.5 0 0 0 -7 0v6.5h18v-6a4 4 0 0 0 -4 -4h-10.5"/><path d="M12 11v-8h4l2 2l-2 2h-4"/><path d="M6 15h1"/></svg>',
                                        'note' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-note" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="13" y1="20" x2="20" y2="13"/><path d="M13 20v-6a1 1 0 0 1 1 -1h6v-7a2 2 0 0 0 -2 -2h-12a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7"/></svg>',
                                        'meeting' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>',
                                        'stage_change' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-right" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="13" y1="18" x2="19" y2="12"/><line x1="13" y1="6" x2="19" y2="12"/></svg>',
                                    ];
                                    $colors = [
                                        'call' => 'bg-green-lt',
                                        'sms' => 'bg-blue-lt',
                                        'email' => 'bg-yellow-lt',
                                        'voicemail' => 'bg-orange-lt',
                                        'direct_mail' => 'bg-teal-lt',
                                        'note' => 'bg-secondary-lt',
                                        'meeting' => 'bg-purple-lt',
                                        'stage_change' => 'bg-cyan-lt',
                                    ];
                                @endphp
                                <span class="avatar avatar-sm {{ $colors[$activity->type] ?? 'bg-secondary-lt' }}">
                                    {!! $icons[$activity->type] ?? strtoupper(substr($activity->type, 0, 1)) !!}
                                </span>
                            </div>
                            <div class="col">
                                <!-- View mode -->
                                <div class="activity-view" id="activity-view-{{ $activity->id }}">
                                    <div class="text-truncate">
                                        <strong>{{ __(ucwords(str_replace('_', ' ', $activity->type))) }}</strong>
                                        @if($activity->subject) - {{ $activity->subject }} @endif
                                    </div>
                                    @if($activity->body)
                                    <div class="text-secondary" style="white-space:pre-line;font-size:13px;">{{ $activity->body }}</div>
                                    @endif
                                    <div class="text-secondary small">
                                        {{ $activity->agent->name ?? '' }} &middot; {{ $activity->logged_at ? $activity->logged_at->diffForHumans() : $activity->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <!-- Edit mode (hidden by default) -->
                                <div class="activity-edit" id="activity-edit-{{ $activity->id }}" style="display:none;">
                                    <div class="mb-1">
                                        <input type="text" class="form-control form-control-sm" id="activity-subject-{{ $activity->id }}" value="{{ $activity->subject }}" placeholder="{{ __('Subject (optional)') }}">
                                    </div>
                                    <div class="mb-1">
                                        <textarea class="form-control form-control-sm" id="activity-body-{{ $activity->id }}" rows="2" placeholder="{{ __('Notes...') }}">{{ $activity->body }}</textarea>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-primary activity-save-btn" data-activity-id="{{ $activity->id }}">{{ __('Save') }}</button>
                                        <button type="button" class="btn btn-sm btn-ghost-secondary activity-cancel-btn" data-activity-id="{{ $activity->id }}">{{ __('Cancel') }}</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button type="button" class="dropdown-item activity-edit-btn" data-activity-id="{{ $activity->id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/><path d="M16 5l3 3"/></svg>
                                            {{ __('Edit') }}
                                        </button>
                                        <button type="button" class="dropdown-item text-danger activity-delete-btn" data-activity-id="{{ $activity->id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
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
    </div>

    <div class="col-md-4">
        <!-- Claim Notification Banner -->
        <div id="claim-alert" class="alert alert-success alert-dismissible d-none mb-3" role="alert">
            <div class="d-flex">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                </div>
                <div id="claim-alert-message"></div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>

        <!-- Associated Deals -->
        @if($lead->deals->count())
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrows-exchange me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 10h14l-4 -4"/><path d="M17 14h-14l4 4"/></svg>
                    {{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transactions') : __('Pipeline Deals') }}
                </h3>
                <div class="card-actions">
                    <span class="badge bg-blue-lt">{{ $lead->deals->count() }}</span>
                </div>
            </div>
            <div class="list-group list-group-flush">
                @foreach($lead->deals->sortByDesc('updated_at') as $deal)
                <a href="{{ route('deals.show', $deal) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">{{ $deal->title }}</div>
                            <div class="d-flex gap-2 mt-1">
                                <span class="badge bg-primary-lt">{{ \App\Models\Deal::stageLabel($deal->stage) }}</span>
                                @if($deal->contract_price)
                                <span class="text-secondary small">{{ Fmt::currency($deal->contract_price) }}</span>
                                @endif
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon text-secondary" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18"/></svg>
                    </div>
                    @if($deal->closing_date)
                    <div class="text-secondary small mt-1">{{ __('Closing') }}: {{ $deal->closing_date->format('M d, Y') }}</div>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Quick Actions') }}</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if(in_array(auth()->user()->tenant->distribution_method ?? '', ['shark_tank', 'hybrid']) && !$lead->agent_id)
                    <button type="button" id="claim-lead-btn" class="btn btn-success" data-lead-id="{{ $lead->id }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                        {{ __('Claim This Lead') }}
                    </button>
                    <hr class="my-1">
                    @endif

                    {{-- Contact --}}
                    @if(($lead->phone && !$lead->do_not_contact) || ($lead->email && !$lead->do_not_contact))
                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Contact') }}</small>
                    @if($lead->phone && !$lead->do_not_contact)
                    <a href="tel:{{ $lead->phone }}" class="btn btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/></svg>
                        {{ __('Call Lead') }}
                    </a>
                    @endif
                    @if($lead->email && !$lead->do_not_contact)
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
                        {{ __('Send Email') }}
                    </button>
                    @endif
                    @endif

                    {{-- AI Tools --}}
                    @if(auth()->user()->tenant->ai_enabled)
                    <small class="text-uppercase text-secondary fw-bold mt-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('AI Tools') }}</small>
                    <button type="button" class="btn btn-outline-purple btn-sm" id="ai-summarize-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        {{ __('Summarize Notes') }}
                    </button>
                    <button type="button" class="btn btn-outline-purple btn-sm" id="ai-score-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        {{ __('AI Score') }}
                    </button>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <button type="button" class="btn btn-outline-purple btn-sm" id="ai-dnc-risk-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        {{ __('DNC Risk Check') }}
                    </button>
                    @endif
                    <button type="button" class="btn btn-outline-purple btn-sm" id="ai-objections-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        {{ __('Objection Responses') }}
                    </button>
                    @endif

                    {{-- Manage --}}
                    <small class="text-uppercase text-secondary fw-bold mt-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">{{ __('Manage') }}</small>
                    <a href="{{ route('leads.edit', $lead) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit Lead') }}</a>
                    <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this lead?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">{{ __('Delete Lead') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Task Form -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Add Task') }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('leads.tasks.store', $lead) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="{{ __('Task title') }}" required>
                    </div>
                    <div class="mb-2">
                        <input type="date" name="due_date" class="form-control form-control-sm" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Add Task') }}</button>
                </form>
            </div>
        </div>

        <!-- Tasks List -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Tasks') }}</h3>
                @if(auth()->user()->tenant->ai_enabled)
                <div class="card-actions">
                    <button type="button" class="btn btn-outline-purple btn-sm" id="ai-suggest-tasks-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                        {{ __('AI Suggest') }}
                    </button>
                </div>
                @endif
            </div>
            <div id="ai-task-suggestions" class="card-body py-2" style="display:none;">
                <div id="ai-task-suggestions-loading" class="text-center py-2">
                    <div class="spinner-border spinner-border-sm text-purple" role="status"></div>
                    <span class="text-secondary ms-2" style="font-size:13px;">{{ __('AI is analyzing...') }}</span>
                </div>
                <div id="ai-task-suggestions-list"></div>
            </div>
            <div class="list-group list-group-flush">
                @forelse($lead->tasks->sortBy('due_date') as $task)
                <div class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <button class="btn btn-sm {{ $task->is_completed ? 'btn-success' : ($task->is_overdue ? 'btn-danger' : 'btn-outline-secondary') }} task-toggle"
                                    data-task-id="{{ $task->id }}">
                                {{ $task->is_completed ? __('Done') : ($task->is_overdue ? __('Overdue') : __('To Do')) }}
                            </button>
                        </div>
                        <div class="flex-fill">
                            <div class="{{ $task->is_completed ? 'text-decoration-line-through text-secondary' : '' }}">
                                {{ $task->title }}
                            </div>
                            <small class="{{ $task->is_overdue ? 'text-danger' : 'text-secondary' }}">
                                {{ __('Due') }}: {{ $task->due_date->format('M d, Y') }}
                            </small>
                        </div>
                        <button type="button" class="btn btn-sm btn-ghost-danger task-delete-btn ms-1" data-task-id="{{ $task->id }}" title="{{ __('Delete task') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                        </button>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-secondary">{{ __('No tasks yet.') }}</div>
                @endforelse
            </div>
        </div>
        <!-- Sequence Enrollments -->
        @if($lead->sequenceEnrollments->count())
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Drip Sequences') }}</h3>
            </div>
            <div class="list-group list-group-flush">
                @foreach($lead->sequenceEnrollments as $enrollment)
                @php
                    $sequence = $enrollment->sequence;
                    $steps = $sequence ? $sequence->steps : collect();
                    $currentStep = $enrollment->current_step;
                    $totalSteps = $steps->count();
                    $nextStep = $steps->where('order', $currentStep + 1)->first();
                    $statusColors = [
                        'active' => 'bg-green-lt',
                        'completed' => 'bg-blue-lt',
                        'paused' => 'bg-yellow-lt',
                        'cancelled' => 'bg-red-lt',
                    ];
                @endphp
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <strong>{{ $sequence->name ?? __('Unknown') }}</strong>
                            <span class="badge {{ $statusColors[$enrollment->status] ?? 'bg-secondary-lt' }} ms-1">{{ __(ucfirst($enrollment->status)) }}</span>
                        </div>
                        <small class="text-secondary">{{ $currentStep }}/{{ $totalSteps }}</small>
                    </div>
                    <!-- Progress bar -->
                    <div class="progress progress-sm mb-2">
                        <div class="progress-bar bg-primary" style="width: {{ $totalSteps > 0 ? round(($currentStep / $totalSteps) * 100) : 0 }}%"></div>
                    </div>
                    <!-- Upcoming step preview -->
                    @if($enrollment->status === 'active' && $nextStep)
                    <div class="small text-secondary">
                        <strong>{{ __('Next') }}:</strong> <span class="badge bg-blue-lt">{{ __(ucwords(str_replace('_', ' ', $nextStep->action_type))) }}</span>
                        {{ __('in :count day', ['count' => $nextStep->delay_days]) }}{{ $nextStep->delay_days != 1 ? 's' : '' }}
                        @if($nextStep->message_template)
                        <div class="mt-1 text-muted" style="font-size: 0.8em;">{{ Str::limit($nextStep->message_template, 120) }}</div>
                        @endif
                    </div>
                    @elseif($enrollment->status === 'completed')
                    <div class="small text-success">{{ __('All steps completed') }}</div>
                    @endif
                    <!-- Step timeline -->
                    <div class="mt-2">
                        @foreach($steps as $step)
                        @php
                            $isDone = $step->order <= $currentStep;
                            $isCurrent = $step->order == $currentStep + 1;
                        @endphp
                        <span class="badge {{ $isDone ? 'bg-green-lt' : ($isCurrent ? 'bg-yellow-lt' : 'bg-secondary-lt') }} me-1 mb-1" title="{{ __('Step') }} {{ $step->order }}: {{ __(ucwords(str_replace('_', ' ', $step->action_type))) }}{{ $step->message_template ? ' - ' . Str::limit($step->message_template, 60) : '' }}">
                            {{ $step->order }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Assignment History -->
        @if(isset($assignmentHistory) && $assignmentHistory->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Assignment History') }}</h3>
            </div>
            <div class="list-group list-group-flush">
                @foreach($assignmentHistory as $entry)
                <div class="list-group-item">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm me-2 {{ $entry->type === 'claim_success' ? 'bg-green-lt' : ($entry->type === 'claim_attempt' ? 'bg-yellow-lt' : 'bg-blue-lt') }}">
                            @if($entry->type === 'claim_success')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                            @elseif($entry->type === 'claim_attempt')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                            @endif
                        </span>
                        <div>
                            <div class="small">
                                @if($entry->type === 'assignment')
                                    {{ __('Assigned to :agent by :by', ['agent' => $entry->new_agent, 'by' => $entry->performed_by]) }}
                                @else
                                    {{ $entry->action }} — {{ $entry->new_agent }}
                                @endif
                            </div>
                            <div class="text-secondary" style="font-size: 0.75rem;">{{ $entry->timestamp->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
<!-- AI Result Modal -->
@if(auth()->user()->tenant->ai_enabled)
<div class="modal modal-blur fade" id="ai-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ai-modal-title">{{ __('AI Assistant') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ai-modal-loading" class="text-center py-4">
                    <div class="spinner-border text-purple" role="status"></div>
                    <p class="text-secondary mt-2">{{ __('AI is thinking...') }}</p>
                </div>
                <div id="ai-modal-result" style="display: none;">
                    <div style="line-height: 1.6;" id="ai-modal-text"></div>
                </div>
                <div id="ai-modal-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-danger" id="ai-dnc-apply-btn" style="display: none;">{{ __('Mark as DNC') }}</button>
                <button type="button" class="btn btn-success" id="ai-save-note-btn" style="display: none;">{{ __('Save as Activity Note') }}</button>
                <button type="button" class="btn btn-primary" id="ai-copy-btn" style="display: none;">{{ __('Copy to Clipboard') }}</button>
                <button type="button" class="btn btn-success" id="ai-apply-score-btn" style="display: none;">{{ __('Apply Score') }}</button>
                <button type="button" class="btn btn-primary" id="ai-use-btn" style="display: none;">{{ __('Use in Activity Form') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Quick-log buttons
document.querySelectorAll('.quick-log-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var type = this.dataset.type;
        // Expand the log activity section if collapsed
        var section = document.getElementById('section-log-activity');
        if (section && !section.classList.contains('show')) {
            var bsCollapse = new bootstrap.Collapse(section, { toggle: true });
        }
        // Set the activity type dropdown
        var typeSelect = document.getElementById('activity-type-select');
        if (typeSelect) {
            typeSelect.value = type;
        }
        // Focus the notes textarea
        var textarea = section ? section.querySelector('textarea[name="body"]') : null;
        if (textarea) {
            setTimeout(function() { textarea.focus(); }, 300);
        }
    });
});

// Shark Tank claim handler
var claimBtn = document.getElementById('claim-lead-btn');
if (claimBtn) {
    claimBtn.addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.textContent = '{{ __('Claiming...') }}';
        fetch('{{ url("/leads") }}/{{ $lead->id }}/claim', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        }).then(function(r) { return r.json(); }).then(function(data) {
            var alertEl = document.getElementById('claim-alert');
            var msgEl = document.getElementById('claim-alert-message');
            if (data.success) {
                alertEl.classList.remove('d-none', 'alert-danger');
                alertEl.classList.add('alert-success');
                msgEl.textContent = data.message || '{{ __('Lead claimed successfully! Reloading...') }}';
                btn.remove();
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                alertEl.classList.remove('d-none', 'alert-success');
                alertEl.classList.add('alert-danger');
                msgEl.textContent = data.error || '{{ __('Failed to claim lead.') }}';
                btn.disabled = false;
                btn.textContent = '{{ __('Claim This Lead') }}';
            }
        }).catch(function() {
            var alertEl = document.getElementById('claim-alert');
            var msgEl = document.getElementById('claim-alert-message');
            alertEl.classList.remove('d-none', 'alert-success');
            alertEl.classList.add('alert-danger');
            msgEl.textContent = '{{ __('Network error. Please try again.') }}';
            btn.disabled = false;
            btn.textContent = '{{ __('Claim This Lead') }}';
        });
    });
}

document.querySelectorAll('.task-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const taskId = this.dataset.taskId;
        fetch('{{ url("/tasks") }}/' + taskId + '/toggle', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
        });
    });
});

// Activity edit buttons
document.querySelectorAll('.activity-edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.activityId;
        document.getElementById('activity-view-' + id).style.display = 'none';
        document.getElementById('activity-edit-' + id).style.display = 'block';
    });
});

// Activity cancel edit buttons
document.querySelectorAll('.activity-cancel-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.activityId;
        document.getElementById('activity-view-' + id).style.display = 'block';
        document.getElementById('activity-edit-' + id).style.display = 'none';
    });
});

// Activity save buttons
document.querySelectorAll('.activity-save-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.activityId;
        var saveBtn = this;
        saveBtn.disabled = true;
        saveBtn.textContent = '{{ __('Saving...') }}';
        fetch('{{ url("/activities") }}/' + id, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                subject: document.getElementById('activity-subject-' + id).value,
                body: document.getElementById('activity-body-' + id).value,
            })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) location.reload();
            else { saveBtn.disabled = false; saveBtn.textContent = '{{ __('Save') }}'; }
        }).catch(function() { saveBtn.disabled = false; saveBtn.textContent = '{{ __('Save') }}'; });
    });
});

// Activity delete buttons
document.querySelectorAll('.activity-delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (!confirm('{{ __('Delete this activity? This cannot be undone.') }}')) return;
        var id = this.dataset.activityId;
        fetch('{{ url("/activities") }}/' + id, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                var el = document.getElementById('activity-' + id);
                if (el) el.remove();
            }
        });
    });
});

document.querySelectorAll('.task-delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (!confirm('{{ __('Delete this task?') }}')) return;
        const taskId = this.dataset.taskId;
        fetch('{{ url("/tasks") }}/' + taskId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
        });
    });
});

@if(auth()->user()->tenant->ai_enabled)
// AI helpers — wrapped in DOMContentLoaded because tabler.min.js (which bundles Bootstrap) is deferred
document.addEventListener('DOMContentLoaded', function() {
var aiModalEl = document.getElementById('ai-modal');
var aiModal = new bootstrap.Modal(aiModalEl);
aiModalEl.addEventListener('hide.bs.modal', function() {
    if (aiModalEl.contains(document.activeElement)) {
        document.activeElement.blur();
    }
});
var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
var leadId = {{ $lead->id }};
var lastAiResult = '';
var lastDraftType = '';
var lastRequestContext = '';

var lastAiScore = null;

function showAiModal(title) {
    document.getElementById('ai-modal-title').textContent = title;
    document.getElementById('ai-modal-loading').style.display = 'block';
    document.getElementById('ai-modal-result').style.display = 'none';
    document.getElementById('ai-modal-error').style.display = 'none';
    document.getElementById('ai-copy-btn').style.display = 'none';
    document.getElementById('ai-apply-score-btn').style.display = 'none';
    document.getElementById('ai-use-btn').style.display = 'none';
    if (document.getElementById('ai-dnc-apply-btn')) document.getElementById('ai-dnc-apply-btn').style.display = 'none';
    if (document.getElementById('ai-save-note-btn')) document.getElementById('ai-save-note-btn').style.display = 'none';
    lastAiScore = null;
    aiModal.show();
}

function showAiResult(text, showUseBtn) {
    lastAiResult = text;
    document.getElementById('ai-modal-loading').style.display = 'none';
    document.getElementById('ai-modal-result').style.display = 'block';
    document.getElementById('ai-modal-text').innerHTML = window.renderAiMarkdown(text);
    document.getElementById('ai-copy-btn').style.display = 'inline-block';
    if (showUseBtn) document.getElementById('ai-use-btn').style.display = 'inline-block';
    if (document.getElementById('ai-save-note-btn')) {
        document.getElementById('ai-save-note-btn').style.display = (lastRequestContext === 'objection') ? 'inline-block' : 'none';
    }
}

function showAiError(msg) {
    document.getElementById('ai-modal-loading').style.display = 'none';
    document.getElementById('ai-modal-error').style.display = 'block';
    document.getElementById('ai-modal-error').textContent = msg;
}

function aiRequest(url, data, title, showUseBtn) {
    showAiModal(title);
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify(data)
    }).then(function(r) { return r.json(); }).then(function(res) {
        if (res.error) { showAiError(res.error); return; }
        var text = res.message || res.summary || res.analysis || res.responses || res.strategy || res.description || res.advice || res.digest || '';
        if (res.scoring) {
            text = '{{ __('AI Score') }}: ' + (res.scoring.score !== null ? res.scoring.score + '/100' : '{{ __('N/A') }}') + '\n';
            text += '{{ __('Confidence') }}: ' + (res.scoring.confidence || '{{ __('N/A') }}') + '\n\n';
            if (res.scoring.factors && res.scoring.factors.length) {
                text += '{{ __('Factors') }}:\n' + res.scoring.factors.map(function(f) { return '  - ' + f; }).join('\n') + '\n\n';
            }
            text += '{{ __('Recommendation') }}: ' + (res.scoring.recommendation || '{{ __('N/A') }}');
            if (res.scoring.score !== null) {
                lastAiScore = res.scoring.score;
                showAiResult(text, false);
                var applyBtn = document.getElementById('ai-apply-score-btn');
                applyBtn.style.display = 'inline-block';
                applyBtn.textContent = '{{ __('Apply Score') }} (' + res.scoring.score + '/100)';
                return;
            }
        }
        showAiResult(text, showUseBtn !== false);
    }).catch(function(e) { showAiError('{{ __('Request failed. Please try again.') }}'); });
}

// Draft Follow-Up button
var draftBtn = document.getElementById('ai-draft-btn');
if (draftBtn) {
    draftBtn.addEventListener('click', function() {
        var typeSelect = document.getElementById('activity-type-select');
        var msgType = typeSelect ? typeSelect.value : 'sms';
        var typeLabels = { sms: '{{ __('SMS Message') }}', email: '{{ __('Email') }}', voicemail: '{{ __('Voicemail Script') }}', call: '{{ __('Call Script') }}', direct_mail: '{{ __('Direct Mail') }}', note: '{{ __('Note') }}', meeting: '{{ __('Meeting Prep') }}' };
        var label = typeLabels[msgType] || msgType.toUpperCase();
        lastDraftType = msgType;
        aiRequest('{{ url("/ai/draft-followup") }}', { lead_id: leadId, type: msgType }, '{{ __('AI Draft') }}: ' + label, true);
    });
}

// Summarize Notes button
var summarizeBtn = document.getElementById('ai-summarize-btn');
if (summarizeBtn) {
    summarizeBtn.addEventListener('click', function() {
        aiRequest('{{ url("/ai/summarize-notes") }}', { lead_id: leadId }, '{{ __('AI Activity Summary') }}', false);
    });
}

// AI Score Lead button
var scoreBtn = document.getElementById('ai-score-btn');
if (scoreBtn) {
    scoreBtn.addEventListener('click', function() {
        aiRequest('{{ url("/ai/score-lead") }}', { lead_id: leadId }, '{{ __('AI Lead Scoring') }}', false);
    });
}

// Apply AI Score button
document.getElementById('ai-apply-score-btn').addEventListener('click', function() {
    var btn = this;
    if (lastAiScore === null) return;
    btn.disabled = true;
    btn.textContent = '{{ __('Saving...') }}';
    fetch('{{ url("/ai/apply-score") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ lead_id: leadId, score: lastAiScore })
    }).then(function(r) { return r.json(); }).then(function(res) {
        if (res.success) {
            btn.textContent = '{{ __('Score Applied!') }}';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            btn.textContent = '{{ __('Failed') }}';
            btn.disabled = false;
        }
    }).catch(function() {
        btn.textContent = '{{ __('Failed') }}';
        btn.disabled = false;
    });
});

// Copy to clipboard
document.getElementById('ai-copy-btn').addEventListener('click', function() {
    navigator.clipboard.writeText(lastAiResult).then(function() {
        var btn = document.getElementById('ai-copy-btn');
        btn.textContent = '{{ __('Copied!') }}';
        setTimeout(function() { btn.textContent = '{{ __('Copy to Clipboard') }}'; }, 2000);
    });
});

// Use in Activity Form
document.getElementById('ai-use-btn').addEventListener('click', function() {
    var bodyField = document.querySelector('textarea[name="body"]');
    if (bodyField) {
        bodyField.value = lastAiResult;
        bodyField.rows = Math.min(10, lastAiResult.split('\n').length + 1);
    }
    // Auto-fill subject based on draft type
    var subjectField = document.querySelector('input[name="subject"]');
    if (subjectField && lastDraftType) {
        var leadName = @json($lead->first_name);
        var subjects = {
            sms: 'SMS follow-up re: {{ $lead->property->address ?? "property" }}',
            email: 'Follow-up on {{ $lead->property->address ?? "your property" }}',
            voicemail: 'Voicemail left re: {{ $lead->property->address ?? "property" }}',
            call: 'Call script re: {{ $lead->property->address ?? "property" }}',
            direct_mail: 'Direct mail sent re: {{ $lead->property->address ?? "property" }}',
            note: 'AI strategy notes for ' + leadName,
            meeting: 'Meeting prep for ' + leadName
        };
        subjectField.value = subjects[lastDraftType] || 'AI-drafted ' + lastDraftType + ' follow-up';
    }
    aiModal.hide();
});
// DNC Risk Check button
var dncBtn = document.getElementById('ai-dnc-risk-btn');
if (dncBtn) {
    dncBtn.addEventListener('click', function() {
        showAiModal('{{ __('AI DNC Risk Check') }}');
        fetch('{{ url("/ai/dnc-risk-check") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ lead_id: leadId })
        }).then(r => r.json()).then(res => {
            if (res.error) { showAiError(res.error); return; }
            var r = res.risk;
            var text = '{{ __('Risk Level') }}: ' + (r.risk_level || '{{ __('unknown') }}').toUpperCase() + '\n\n';
            if (r.flags && r.flags.length) {
                text += '{{ __('Flags') }}:\n' + r.flags.map(function(f) { return '  - ' + f; }).join('\n') + '\n\n';
            }
            text += '{{ __('Recommendation') }}: ' + (r.recommendation || '{{ __('N/A') }}');
            showAiResult(text, false);
            // Show Mark as DNC button if risk is high or medium
            var dncApplyBtn = document.getElementById('ai-dnc-apply-btn');
            if (dncApplyBtn) {
                dncApplyBtn.style.display = (r.risk_level === 'high' || r.risk_level === 'medium') ? 'inline-block' : 'none';
            }
        }).catch(function() { showAiError('{{ __('Request failed.') }}'); });
    });
}

// Objection Responses button
var objBtn = document.getElementById('ai-objections-btn');
if (objBtn) {
    objBtn.addEventListener('click', function() {
        lastRequestContext = 'objection';
        aiRequest('{{ url("/ai/objection-responses") }}', { lead_id: leadId }, '{{ __('AI Objection Responses') }}', false);
    });
}

// Mark as DNC from AI
var dncApplyBtn = document.getElementById('ai-dnc-apply-btn');
if (dncApplyBtn) {
    dncApplyBtn.addEventListener('click', function() {
        if (!confirm('{{ __('Mark this lead as Do Not Contact? This will prevent future outreach.') }}')) return;
        var btn = this;
        btn.disabled = true;
        btn.textContent = '{{ __('Marking...') }}';
        fetch('{{ url("/ai/apply-lead-dnc") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ lead_id: leadId })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.success) {
                btn.textContent = '{{ __('Marked!') }}';
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline-danger');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                btn.textContent = '{{ __('Failed') }}';
                btn.disabled = false;
            }
        }).catch(function() {
            btn.textContent = '{{ __('Failed') }}';
            btn.disabled = false;
        });
    });
}

// Save AI result as Activity Note
var saveNoteBtn = document.getElementById('ai-save-note-btn');
if (saveNoteBtn) {
    saveNoteBtn.addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.textContent = '{{ __('Saving...') }}';
        var formData = new FormData();
        formData.append('type', 'note');
        formData.append('subject', document.getElementById('ai-modal-title').textContent);
        formData.append('body', lastAiResult);
        fetch('{{ url("/leads/" . $lead->id . "/activities") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
        }).then(function(r) { return r.json(); }).then(function(res) {
            btn.textContent = '{{ __('Saved!') }}';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
            setTimeout(function() { location.reload(); }, 800);
        }).catch(function() {
            btn.textContent = '{{ __('Failed') }}';
            btn.disabled = false;
        });
    });
}

// Property AI buttons (offer strategy + description) — use existing modal
document.querySelectorAll('.ai-prop-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var action = this.dataset.action;
        var propId = this.dataset.propId;
        var titles = { 'offer-strategy': '{{ __('AI Offer Strategy') }}', 'property-description': '{{ __('AI Property Description') }}', 'comparable-sales': '{{ __('AI Comparable Sales Analysis') }}' };
        showAiModal(titles[action] || '{{ __('AI') }}');
        fetch('{{ url("/ai") }}/' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ property_id: parseInt(propId) })
        }).then(r => r.json()).then(res => {
            if (res.error) { showAiError(res.error); return; }
            showAiResult(res.strategy || res.description || res.analysis || '', false);
        }).catch(function() { showAiError('{{ __('Request failed.') }}'); });
    });
});
// AI Suggest Tasks
var suggestTasksBtn = document.getElementById('ai-suggest-tasks-btn');
if (suggestTasksBtn) {
    suggestTasksBtn.addEventListener('click', function() {
        var container = document.getElementById('ai-task-suggestions');
        var loading = document.getElementById('ai-task-suggestions-loading');
        var list = document.getElementById('ai-task-suggestions-list');
        container.style.display = 'block';
        loading.style.display = 'block';
        list.innerHTML = '';
        suggestTasksBtn.disabled = true;

        fetch('{{ url("/ai/suggest-tasks") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ lead_id: leadId })
        }).then(function(r) { return r.json(); }).then(function(res) {
            loading.style.display = 'none';
            suggestTasksBtn.disabled = false;
            if (res.error) {
                list.innerHTML = '<div class="text-danger small py-1">' + res.error + '</div>';
                return;
            }
            if (!res.tasks || !res.tasks.length) {
                list.innerHTML = '<div class="text-secondary small py-1">{{ __('No suggestions generated.') }}</div>';
                return;
            }
            var priorityColors = { high: 'bg-red-lt', medium: 'bg-yellow-lt', low: 'bg-green-lt' };
            var html = '';
            res.tasks.forEach(function(task) {
                var dueDate = new Date();
                dueDate.setDate(dueDate.getDate() + task.days_from_now);
                var dueDateStr = dueDate.toISOString().split('T')[0];
                var dueDateLabel = dueDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                html += '<div class="d-flex align-items-start py-2 border-bottom">';
                html += '<div class="flex-fill">';
                html += '<div class="fw-medium" style="font-size:13px;">' + task.title + '</div>';
                html += '<div class="text-secondary" style="font-size:11px;">';
                html += '<span class="badge ' + (priorityColors[task.priority] || 'bg-secondary-lt') + ' me-1">' + task.priority + '</span>';
                html += '{{ __('Due') }} ' + dueDateLabel;
                if (task.reason) html += ' &middot; ' + task.reason;
                html += '</div></div>';
                html += '<button type="button" class="btn btn-sm btn-outline-primary ms-2 ai-add-task-btn" data-title="' + task.title.replace(/"/g, '&quot;') + '" data-due="' + dueDateStr + '" style="white-space:nowrap;">+ {{ __('Add') }}</button>';
                html += '</div>';
            });
            list.innerHTML = html;

            // Wire up add buttons
            list.querySelectorAll('.ai-add-task-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var addBtn = this;
                    addBtn.disabled = true;
                    addBtn.textContent = '{{ __('Adding...') }}';
                    var formData = new FormData();
                    formData.append('title', addBtn.dataset.title);
                    formData.append('due_date', addBtn.dataset.due);
                    formData.append('_token', csrfToken);

                    fetch('{{ url("/leads/" . $lead->id . "/tasks") }}', {
                        method: 'POST',
                        headers: { 'Accept': 'text/html' },
                        body: formData
                    }).then(function(r) {
                        if (r.ok || r.redirected) {
                            addBtn.textContent = '{{ __('Added') }}';
                            addBtn.classList.remove('btn-outline-primary');
                            addBtn.classList.add('btn-success');
                            // Reload to show new task in list
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            addBtn.textContent = '{{ __('Failed') }}';
                            addBtn.disabled = false;
                        }
                    }).catch(function() {
                        addBtn.textContent = '{{ __('Failed') }}';
                        addBtn.disabled = false;
                    });
                });
            });
        }).catch(function() {
            loading.style.display = 'none';
            suggestTasksBtn.disabled = false;
            list.innerHTML = '<div class="text-danger small py-1">{{ __('Request failed. Try again.') }}</div>';
        });
    });
}
// AI Snapshot button
// Auto-load AI briefing
(function() {
    var briefingText = document.getElementById('ai-briefing-text');
    var briefingLoading = document.getElementById('ai-briefing-loading');
    var briefingError = document.getElementById('ai-briefing-error');
    var briefingRefresh = document.getElementById('ai-briefing-refresh');
    var briefingLinks = document.getElementById('ai-briefing-links');
    if (!briefingText) return;

    var typeLabels = { deal: '{{ ($businessMode ?? "wholesale") === "realestate" ? __("Transaction") : __("Deal") }}', lead: '{{ __("Lead") }}', buyer: '{{ ($businessMode ?? "wholesale") === "realestate" ? __("Client") : __("Buyer") }}', property: '{{ __("Property") }}' };
    var typeColors = { deal: 'bg-blue-lt', lead: 'bg-green-lt', buyer: 'bg-orange-lt', property: 'bg-cyan-lt' };
    var isRealEstate = {{ ($businessMode ?? 'wholesale') === 'realestate' ? 'true' : 'false' }};
    var fmtCur = function(v) { return v ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v) : ''; };
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
            if (link.price) text += ' — ' + fmtCur(link.price);
            if (!isRealEstate && link.arv) text += ' — ARV ' + fmtCur(link.arv);
            if (isRealEstate && link.price) text += ' — ' + '{{ __("List") }} ' + fmtCur(link.price);
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
        var url = '{{ url("/ai/lead-briefing") }}' + (force ? '?refresh=1' : '');
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ lead_id: leadId })
        }).then(function(r) { return r.json(); }).then(function(res) {
            briefingLoading.style.display = 'none';
            if (res.error === 'disabled') {
                document.getElementById('ai-briefing-card').style.display = 'none'; return;
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

// AI Email Subject Lines button
var suggestSubjectsBtn = document.getElementById('ai-suggest-subjects-btn');
if (suggestSubjectsBtn) {
    suggestSubjectsBtn.addEventListener('click', function() {
        var emailBody = document.getElementById('email-body').value;
        if (!emailBody.trim()) {
            alert('{{ __('Please write the email body first, so AI can suggest relevant subject lines.') }}');
            document.getElementById('email-body').focus();
            return;
        }
        var container = document.getElementById('ai-subject-suggestions');
        var loading = document.getElementById('ai-subject-loading');
        var list = document.getElementById('ai-subject-list');
        container.style.display = 'block';
        loading.style.display = 'block';
        list.innerHTML = '';
        suggestSubjectsBtn.disabled = true;

        fetch('{{ url("/ai/email-subject-lines") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ lead_id: leadId, body: emailBody })
        }).then(function(r) { return r.json(); }).then(function(res) {
            loading.style.display = 'none';
            suggestSubjectsBtn.disabled = false;
            if (res.error) {
                list.innerHTML = '<div class="text-danger small py-1">' + res.error + '</div>';
                return;
            }
            var subjects = res.subject_lines;
            if (!subjects || !subjects.length) {
                list.innerHTML = '<div class="text-secondary small py-1">{{ __('No suggestions generated.') }}</div>';
                return;
            }
            var styleColors = { direct: 'bg-blue-lt', curiosity: 'bg-purple-lt', urgency: 'bg-red-lt' };
            var html = '';
            subjects.forEach(function(item) {
                var badgeClass = styleColors[item.style] || 'bg-secondary-lt';
                html += '<div class="d-flex align-items-center py-1 border-bottom ai-subject-option" style="cursor:pointer;" data-subject="' + (item.subject || '').replace(/"/g, '&quot;') + '">';
                html += '<span class="badge ' + badgeClass + ' me-2" style="font-size:0.7rem;">' + (item.style || 'direct') + '</span>';
                html += '<span class="flex-fill" style="font-size:0.85rem;">' + (item.subject || '') + '</span>';
                html += '</div>';
            });
            list.innerHTML = html;

            // Wire click to fill subject
            list.querySelectorAll('.ai-subject-option').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    document.getElementById('email-subject').value = this.dataset.subject;
                    container.style.display = 'none';
                });
            });
        }).catch(function() {
            loading.style.display = 'none';
            suggestSubjectsBtn.disabled = false;
            list.innerHTML = '<div class="text-danger small py-1">{{ __('Request failed. Try again.') }}</div>';
        });
    });
}
// AI Draft Full Email button
var draftEmailBtn = document.getElementById('ai-draft-email-btn');
if (draftEmailBtn) {
    draftEmailBtn.addEventListener('click', function() {
        var btn = this;
        var loadingEl = document.getElementById('ai-draft-email-loading');
        btn.disabled = true;
        loadingEl.style.display = 'block';

        fetch('{{ url("/ai/draft-email") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ lead_id: leadId })
        }).then(function(r) { return r.json(); }).then(function(res) {
            loadingEl.style.display = 'none';
            btn.disabled = false;
            if (res.error) {
                alert(res.error);
                return;
            }
            if (res.body) {
                document.getElementById('email-body').value = res.body;
            }
            if (res.subject) {
                document.getElementById('email-subject').value = res.subject;
            }
            // Also show subject alternatives
            if (res.subject_lines && res.subject_lines.length > 1) {
                var container = document.getElementById('ai-subject-suggestions');
                var list = document.getElementById('ai-subject-list');
                var styleColors = { direct: 'bg-blue-lt', curiosity: 'bg-purple-lt', urgency: 'bg-red-lt' };
                var html = '<div class="text-secondary small mb-1">{{ __('Alternative subjects:') }}</div>';
                res.subject_lines.forEach(function(item, i) {
                    if (i === 0) return;
                    var badgeClass = styleColors[item.style] || 'bg-secondary-lt';
                    html += '<div class="d-flex align-items-center py-1 border-bottom ai-subject-option" style="cursor:pointer;" data-subject="' + (item.subject || '').replace(/"/g, '&quot;') + '">';
                    html += '<span class="badge ' + badgeClass + ' me-2" style="font-size:0.7rem;">' + (item.style || 'direct') + '</span>';
                    html += '<span class="flex-fill" style="font-size:0.85rem;">' + (item.subject || '') + '</span>';
                    html += '</div>';
                });
                list.innerHTML = html;
                container.style.display = 'block';
                list.querySelectorAll('.ai-subject-option').forEach(function(opt) {
                    opt.addEventListener('click', function() {
                        document.getElementById('email-subject').value = this.dataset.subject;
                        container.style.display = 'none';
                    });
                });
            }
        }).catch(function() {
            loadingEl.style.display = 'none';
            btn.disabled = false;
            alert('{{ __('Request failed. Please try again.') }}');
        });
    });
}

}); // end DOMContentLoaded
@endif

// Send Email modal: template loading
var templateSelect = document.getElementById('email-template-select');
if (templateSelect) {
    templateSelect.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        if (opt.value) {
            document.getElementById('email-subject').value = opt.dataset.subject || '';
            document.getElementById('email-body').value = opt.dataset.body || '';
        }
    });
}

// Track recently viewed
if (window.trackRecentlyViewed) {
    window.trackRecentlyViewed('lead', {{ $lead->id }}, @json($lead->full_name), '{{ route("leads.show", $lead) }}');
}
</script>
@endpush

@if($lead->email && !$lead->do_not_contact)
<!-- Send Email Modal -->
<div class="modal modal-blur fade" id="sendEmailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('leads.sendEmail', $lead) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 7 12 13 21 7"/></svg>
                        {{ __('Send Email to :name', ['name' => $lead->full_name]) }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('To') }}</label>
                        <input type="text" class="form-control" value="{{ $lead->email }}" disabled>
                    </div>
                    @php
                        $emailTemplates = \Illuminate\Support\Facades\DB::table('email_templates')
                            ->where('tenant_id', auth()->user()->tenant_id)
                            ->orderBy('name')
                            ->get();
                    @endphp
                    @if($emailTemplates->count())
                    <div class="mb-3">
                        <label class="form-label">{{ __('Template (optional)') }}</label>
                        <select class="form-select" id="email-template-select">
                            <option value="">{{ __('-- Select a template --') }}</option>
                            @foreach($emailTemplates as $tpl)
                            <option value="{{ $tpl->id }}" data-subject="{{ e($tpl->subject) }}" data-body="{{ e($tpl->body) }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">{{ __('Subject') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="subject" class="form-control" id="email-subject" required placeholder="{{ __('Email subject line') }}">
                            @if(auth()->user()->tenant->ai_enabled)
                            <button type="button" class="btn btn-outline-purple btn-sm" id="ai-suggest-subjects-btn" title="{{ __('AI Suggest Subject Lines') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                {{ __('AI Suggest') }}
                            </button>
                            @endif
                        </div>
                        <div id="ai-subject-suggestions" style="display: none;" class="mt-2">
                            <div id="ai-subject-loading" class="text-center py-2" style="display: none;">
                                <div class="spinner-border spinner-border-sm text-purple" role="status"></div>
                                <span class="text-secondary ms-1" style="font-size: 13px;">{{ __('Generating suggestions...') }}</span>
                            </div>
                            <div id="ai-subject-list"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">{{ __('Message') }} <span class="text-danger">*</span></label>
                            @if(auth()->user()->tenant->ai_enabled)
                            <button type="button" class="btn btn-outline-purple btn-sm" id="ai-draft-email-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sparkles" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 18a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm0 -12a2 2 0 0 1 2 2a2 2 0 0 1 2 -2a2 2 0 0 1 -2 -2a2 2 0 0 1 -2 2zm-7 12a6 6 0 0 1 6 -6a6 6 0 0 1 -6 -6a6 6 0 0 1 -6 6a6 6 0 0 1 6 6z"/></svg>
                                {{ __('AI Draft Email') }}
                            </button>
                            @endif
                        </div>
                        <textarea name="body" class="form-control" id="email-body" rows="8" required placeholder="{{ __('Write your message...') }}"></textarea>
                        <div id="ai-draft-email-loading" class="text-center py-2" style="display: none;">
                            <div class="spinner-border spinner-border-sm text-purple" role="status"></div>
                            <span class="text-secondary ms-1" style="font-size: 13px;">{{ __('AI is drafting your email...') }}</span>
                        </div>
                    </div>
                    <div class="alert alert-info py-2">
                        <small>
                            <strong>{{ __('Merge Tags:') }}</strong>
                            <code>{first_name}</code> <code>{last_name}</code> <code>{full_name}</code> <code>{email}</code> <code>{phone}</code> <code>{address}</code> <code>{company_name}</code>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="10" y1="14" x2="21" y2="3"/><path d="M21 3l-6.5 18a0.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a0.55 .55 0 0 1 0 -1l18 -6.5"/></svg>
                        {{ __('Send Email') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
