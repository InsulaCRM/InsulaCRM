@extends('layouts.app')
@section('title', __('Error Detail'))
@section('page-title', __('Bug Reports'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('error-logs.index') }}">{{ __('Bug Reports') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Error Details') }}</li>
@endsection

@section('content')
<div class="container-xl">
    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('error-logs.index') }}" class="btn btn-outline-secondary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="12" x2="11" y2="18"/><line x1="5" y1="12" x2="11" y2="6"/></svg>
            {{ __('Back to Bug Reports') }}
        </a>
        <a href="{{ route('error-logs.export', $error->id) }}" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><polyline points="7 11 12 16 17 11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
            {{ __('Export Bug Report') }}
        </a>
        <form method="POST" action="{{ route('error-logs.toggle', $error->id) }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-sm {{ $error->is_resolved ? 'btn-outline-warning' : 'btn-success' }}">
                {{ $error->is_resolved ? __('Reopen') : __('Mark Resolved') }}
            </button>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Error Message') }}</h3>
                    <div class="card-actions">
                        @if($error->level === 'critical')
                            <span class="badge bg-red-lt">{{ $error->level }}</span>
                        @elseif($error->level === 'error')
                            <span class="badge bg-orange-lt">{{ $error->level }}</span>
                        @else
                            <span class="badge bg-yellow-lt">{{ $error->level }}</span>
                        @endif
                        @if($error->is_resolved)
                            <span class="badge bg-green-lt ms-1">{{ __('Resolved') }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>{{ $error->message }}</strong></p>
                    @if($error->exception_class)
                        <div class="text-muted small">{{ $error->exception_class }}</div>
                    @endif
                </div>
            </div>

            @if($error->trace)
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Stack Trace') }}</h3>
                </div>
                <div class="card-body p-0">
                    <pre class="m-0 p-3" style="max-height: 500px; overflow: auto; font-size: 0.8rem; white-space: pre-wrap; word-break: break-all;">{{ $error->trace }}</pre>
                </div>
            </div>
            @endif

            @if($error->context)
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Context') }}</h3>
                </div>
                <div class="card-body p-0">
                    <pre class="m-0 p-3" style="max-height: 300px; overflow: auto; font-size: 0.8rem;">{{ json_encode(json_decode($error->context, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Details') }}</h3>
                </div>
                <table class="table table-vcenter card-table">
                    <tbody>
                        <tr>
                            <td class="text-muted">{{ __('File') }}</td>
                            <td class="small">{{ $error->file ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Line') }}</td>
                            <td>{{ $error->line ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('URL') }}</td>
                            <td class="small" style="word-break: break-all;">{{ $error->url ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Method') }}</td>
                            <td>{{ $error->method ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('IP') }}</td>
                            <td>{{ $error->ip_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('User Agent') }}</td>
                            <td class="small">{{ Str::limit($error->user_agent ?? '-', 60) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Occurred') }}</td>
                            <td>{{ \Carbon\Carbon::parse($error->created_at)->format('M d, Y H:i:s') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
