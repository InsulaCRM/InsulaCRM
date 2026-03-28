@extends('layouts.app')

@section('title', $leadList->name)
@section('page-title', $leadList->name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('lists.index') }}">{{ __('Lists') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $leadList->name ?? __('Import Details') }}</li>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title">
            <span class="badge bg-blue-lt me-2">{{ __(ucwords(str_replace('_', ' ', $leadList->type))) }}</span>
            {{ $leadList->name }}
        </h3>
        <div class="card-actions">
            <span class="text-secondary me-3">{{ number_format($leadList->leads->count()) }} {{ __('leads') }}</span>
            <a href="{{ route('lists.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Back to Lists') }}</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Phone') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Source') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Temperature') }}</th>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <th>{{ __('Motivation') }}</th>
                    @endif
                    <th>{{ __('Agent') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leadList->leads as $lead)
                <tr>
                    <td>
                        <a href="{{ route('leads.show', $lead) }}">{{ $lead->first_name }} {{ $lead->last_name }}</a>
                    </td>
                    <td class="text-secondary">{{ $lead->phone ?? '-' }}</td>
                    <td class="text-secondary">{{ $lead->email ?? '-' }}</td>
                    <td>
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
                    </td>
                    <td><span class="badge bg-secondary-lt">{{ __(ucwords(str_replace('_', ' ', $lead->status))) }}</span></td>
                    <td>
                        @php
                            $tempColors = ['hot' => 'bg-red-lt', 'warm' => 'bg-yellow-lt', 'cold' => 'bg-azure-lt'];
                        @endphp
                        <span class="badge {{ $tempColors[$lead->temperature] ?? 'bg-secondary-lt' }}">@if($lead->temperature === 'hot')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2-2.96 0-7-1-8 0 3.038-1.773 4.741-3 6-1.226 1.26-2 3.24-2 5a6 6 0 1 0 12 0c0-1.532-1.056-3.94-2-5-1.786 3-2.791 3-4 2z"/></svg>@elseif($lead->temperature === 'warm')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8-9v1m8 8h1m-9 8v1m-6.4-15.4l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>@elseif($lead->temperature === 'cold')<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 4l2 1l2-1"/><path d="M12 2v6.5l3 1.72"/><path d="M17.928 6.268l.134 2.232l1.866 1.232"/><path d="M20.66 7l-5.629 3.25l.01 3.458"/><path d="M19.928 14.268l-1.866 1.232l-.134 2.232"/><path d="M20.66 17l-5.629-3.25l-2.99 1.738"/><path d="M14 20l-2-1l-2 1"/><path d="M12 22v-6.5l-3-1.72"/><path d="M6.072 17.732l-.134-2.232l-1.866-1.232"/><path d="M3.34 17l5.629-3.25l-.01-3.458"/><path d="M4.072 9.732l1.866-1.232l.134-2.232"/><path d="M3.34 7l5.629 3.25l2.99-1.738"/></svg>@endif {{ __(ucfirst($lead->temperature)) }}</span>
                    </td>
                    @if(($businessMode ?? 'wholesale') === 'wholesale')
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-fill" style="height: 4px; min-width: 40px;">
                                <div class="progress-bar {{ $lead->motivation_score >= 70 ? 'bg-green' : ($lead->motivation_score >= 40 ? 'bg-yellow' : 'bg-red') }}" style="width: {{ $lead->motivation_score }}%"></div>
                            </div>
                            <span class="small text-secondary" style="min-width: 30px;">{{ $lead->motivation_score }}%</span>
                        </div>
                    </td>
                    @endif
                    <td class="text-secondary">{{ $lead->agent->name ?? __('Unassigned') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-secondary">{{ __('No leads in this list.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
