@extends('layouts.app')

@section('title', __('Workflow Logs') . ' - ' . $workflow->name)
@section('page-title', __('Run Logs'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('workflows.index') }}">{{ __('Workflows') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('workflows.edit', $workflow) }}">{{ $workflow->name }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Logs') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            {{ __('Run Logs') }}
            <span class="text-muted ms-2">({{ $logs->total() }})</span>
        </h3>
        <div class="card-actions">
            <form method="GET" action="{{ route('workflows.logs', $workflow) }}" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['started', 'completed', 'failed', 'skipped', 'waiting'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ __(ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Step') }}</th>
                    <th>{{ __('Model') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Result') }}</th>
                    <th>{{ __('Scheduled / Executed') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-secondary">
                        {{ $log->created_at->format('M d, Y H:i') }}
                    </td>
                    <td>
                        @if($log->step)
                            <span class="badge bg-{{ $log->step->type === 'action' ? 'primary' : ($log->step->type === 'condition' ? 'warning' : 'info') }}-lt">
                                {{ __(ucfirst($log->step->type)) }}
                            </span>
                            <span class="text-secondary small ms-1">{{ \Illuminate\Support\Str::limit($log->step->summary ?? '', 40) }}</span>
                        @else
                            <span class="text-muted">{{ __('Deleted step') }}</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $modelLabel = '';
                            $modelUrl = null;
                            if ($log->model_type && $log->model_id) {
                                $shortType = class_basename($log->model_type);
                                $modelLabel = $shortType . ' #' . $log->model_id;
                                if ($shortType === 'Lead') $modelUrl = url('/leads/' . $log->model_id);
                                elseif ($shortType === 'Deal') $modelUrl = url('/pipeline/' . $log->model_id);
                            }
                        @endphp
                        @if($modelUrl)
                            <a href="{{ $modelUrl }}">{{ $modelLabel }}</a>
                        @elseif($modelLabel)
                            {{ $modelLabel }}
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $log->status_color }}-lt">{{ $log->status_label }}</span>
                    </td>
                    <td class="text-secondary">
                        {{ \Illuminate\Support\Str::limit($log->result ?? '', 80) }}
                    </td>
                    <td class="text-secondary small">
                        @if($log->scheduled_at)
                            <span title="{{ __('Scheduled') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
                                {{ $log->scheduled_at->format('H:i') }}
                            </span>
                        @endif
                        @if($log->executed_at)
                            <span class="ms-2" title="{{ __('Executed') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm text-green" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                {{ $log->executed_at->format('H:i') }}
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-secondary py-4">
                        <p class="mb-1">{{ __('No run logs yet.') }}</p>
                        <p class="text-muted">{{ __('Logs will appear here once the workflow is triggered.') }}</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $logs->withQueryString()->links('vendor.pagination.tabler') }}
    </div>
    @endif
</div>
@endsection
