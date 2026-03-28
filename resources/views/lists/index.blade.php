@extends('layouts.app')

@section('title', __('Lead Lists'))
@section('page-title', __('Lead Lists'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Lists') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Lead Lists') }}</h3>
        <div class="card-actions">
            <a href="{{ route('lists.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                {{ __('Import List') }}
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Record Count') }}</th>
                    <th>{{ __('Imported Date') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($lists as $list)
                <tr>
                    <td><a href="{{ route('lists.show', $list) }}">{{ $list->name }}</a></td>
                    <td>
                        <span class="badge bg-blue-lt">{{ __(ucwords(str_replace('_', ' ', $list->type))) }}</span>
                    </td>
                    <td class="text-secondary">{{ number_format($list->record_count ?? 0) }}</td>
                    <td class="text-secondary">{{ $list->imported_at ? $list->imported_at->format('M d, Y') : '-' }}</td>
                    <td>
                        <form method="POST" action="{{ route('lists.destroy', $list) }}" onsubmit="return confirm('{{ __('Delete this list?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost-danger btn-sm">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">
                        <p class="mb-2">{{ __('No lists found.') }}</p>
                        <a href="{{ route('lists.create') }}" class="btn btn-outline-primary btn-sm">{{ __('Import your first list') }}</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
