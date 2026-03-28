@extends('layouts.app')

@section('title', $sequence->name)
@section('page-title', $sequence->name)

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('sequences.index') }}">{{ __('Sequences') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $sequence->name }}</li>
@endsection

@section('content')
<div class="row row-deck row-cards">
    {{-- Sequence Info --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Sequence Details') }}</h3>
                <div class="card-actions">
                    @if($sequence->is_active)
                        <span class="badge bg-green-lt me-2">{{ __('Active') }}</span>
                    @else
                        <span class="badge bg-red-lt me-2">{{ __('Inactive') }}</span>
                    @endif
                    <a href="{{ route('sequences.edit', $sequence) }}" class="btn btn-outline-primary btn-sm">{{ __('Edit') }}</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Steps Timeline --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Steps') }} ({{ $sequence->steps->count() }})</h3>
            </div>
            <div class="card-body">
                @forelse($sequence->steps as $step)
                <div class="d-flex align-items-start mb-3 {{ !$loop->last ? 'pb-3 border-bottom' : '' }}">
                    <div class="flex-shrink-0 me-3 text-center" style="min-width: 40px;">
                        <span class="avatar avatar-sm {{ $loop->first ? 'bg-primary-lt' : 'bg-secondary-lt' }}">{{ $step->order }}</span>
                        @unless($loop->last)
                        <div class="border-start mx-auto mt-1" style="height: 20px; width: 0;"></div>
                        @endunless
                    </div>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-blue-lt">{{ __(ucwords(str_replace('_', ' ', $step->action_type))) }}</span>
                            <span class="text-secondary small">
                                @if($step->delay_days == 0)
                                    {{ __('Immediately') }}
                                @else
                                    {{ __('After :count day', ['count' => $step->delay_days]) }}{{ $step->delay_days != 1 ? 's' : '' }}
                                @endif
                            </span>
                        </div>
                        <p class="text-secondary mb-0">{{ Str::limit($step->message_template, 120) }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center text-secondary py-3">
                    <p class="mb-2">{{ __('No steps configured.') }}</p>
                    <a href="{{ route('sequences.edit', $sequence) }}" class="btn btn-outline-primary btn-sm">{{ __('Add steps') }}</a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Enrolled Leads --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Enrolled Leads') }}</h3>
            </div>
            <div class="card-body border-bottom py-3">
                <form method="POST" action="{{ route('sequences.enroll', $sequence) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Enroll a Lead') }}</label>
                        <select name="lead_id" class="form-select @error('lead_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select a lead...') }}</option>
                            @foreach($leads as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->full_name }} ({{ $lead->email ?? $lead->phone ?? __('No contact') }})</option>
                            @endforeach
                        </select>
                        @error('lead_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">{{ __('Enroll') }}</button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('Lead Name') }}</th>
                            <th>{{ __('Enrollment Date') }}</th>
                            <th>{{ __('Current Step') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sequence->enrollments as $enrollment)
                        <tr>
                            <td>
                                <a href="{{ route('leads.show', $enrollment->lead_id) }}">{{ $enrollment->lead->full_name ?? __('Unknown') }}</a>
                            </td>
                            <td class="text-secondary">{{ $enrollment->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-fill" style="height: 4px; min-width: 60px;" role="progressbar" aria-valuenow="{{ $enrollment->current_step }}" aria-valuemin="0" aria-valuemax="{{ $sequence->steps->count() }}" aria-label="{{ __('Step :current of :total', ['current' => $enrollment->current_step, 'total' => $sequence->steps->count()]) }}">
                                        <div class="progress-bar" style="width: {{ $sequence->steps->count() > 0 ? round($enrollment->current_step / $sequence->steps->count() * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="text-secondary small">{{ $enrollment->current_step }}/{{ $sequence->steps->count() }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-lt',
                                        'completed' => 'bg-blue-lt',
                                        'paused' => 'bg-yellow-lt',
                                        'cancelled' => 'bg-red-lt',
                                    ];
                                @endphp
                                <span class="badge {{ $statusColors[$enrollment->status] ?? 'bg-secondary-lt' }}">{{ __(ucfirst($enrollment->status)) }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('sequences.unenroll', [$sequence, $enrollment->lead_id]) }}" onsubmit="return confirm('{{ __('Unenroll this lead?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost-danger btn-sm">{{ __('Unenroll') }}</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary">{{ __('No leads enrolled.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
