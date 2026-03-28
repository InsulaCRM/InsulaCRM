@extends('layouts.app')
@section('title', __('Bug Reports'))
@section('page-title', __('Bug Reports'))

@section('content')
<div class="container-xl">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <div class="d-flex">
                <div>{{ session('success') }}</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Captured Errors') }}</h3>
            <div class="card-actions">
                <form method="POST" action="{{ route('error-logs.clear') }}" class="d-inline" onsubmit="return confirm('{{ __('Delete all resolved errors?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                        {{ __('Clear Resolved') }}
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body border-bottom py-3">
            <form method="GET" action="{{ route('error-logs.index') }}" class="row g-2">
                <div class="col-auto">
                    <select name="level" class="form-select form-select-sm">
                        <option value="">{{ __('All Levels') }}</option>
                        @foreach(['error', 'warning', 'critical'] as $lvl)
                            <option value="{{ $lvl }}" {{ request('level') === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="resolved" class="form-select form-select-sm">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="no" {{ request('resolved') === 'no' ? 'selected' : '' }}>{{ __('Unresolved') }}</option>
                        <option value="yes" {{ request('resolved') === 'yes' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search...') }}" value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>{{ __('Level') }}</th>
                        <th>{{ __('Message') }}</th>
                        <th>{{ __('File') }}</th>
                        <th>{{ __('URL') }}</th>
                        <th>{{ __('When') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($errors as $error)
                    <tr>
                        <td>
                            @if($error->level === 'critical')
                                <span class="badge bg-red-lt">{{ $error->level }}</span>
                            @elseif($error->level === 'error')
                                <span class="badge bg-orange-lt">{{ $error->level }}</span>
                            @else
                                <span class="badge bg-yellow-lt">{{ $error->level }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('error-logs.show', $error->id) }}">{{ Str::limit($error->message, 80) }}</a>
                        </td>
                        <td class="text-muted small">{{ $error->file ? basename($error->file) . ':' . $error->line : '-' }}</td>
                        <td class="text-muted small">{{ $error->url ? Str::limit($error->url, 40) : '-' }}</td>
                        <td class="text-muted small">{{ \Carbon\Carbon::parse($error->created_at)->diffForHumans() }}</td>
                        <td>
                            @if($error->is_resolved)
                                <span class="badge bg-green-lt">{{ __('Resolved') }}</span>
                            @else
                                <span class="badge bg-yellow-lt">{{ __('Open') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('error-logs.show', $error->id) }}">{{ __('View Details') }}</a>
                                    <a class="dropdown-item" href="{{ route('error-logs.export', $error->id) }}">{{ __('Export Report') }}</a>
                                    <form method="POST" action="{{ route('error-logs.toggle', $error->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item">{{ $error->is_resolved ? __('Mark Open') : __('Mark Resolved') }}</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('No errors captured yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($errors->hasPages())
        <div class="card-footer d-flex align-items-center">
            {{ $errors->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
