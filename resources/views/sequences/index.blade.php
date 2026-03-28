@extends('layouts.app')

@section('title', __('Drip Sequences'))
@section('page-title', __('Drip Sequences'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Sequences') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Drip Sequences') }}</h3>
        <div class="card-actions">
            <a href="{{ route('sequences.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Create Sequence') }}
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Steps') }}</th>
                    <th>{{ __('Enrollments') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sequences as $sequence)
                <tr>
                    <td>
                        <a href="{{ route('sequences.show', $sequence) }}">{{ $sequence->name }}</a>
                    </td>
                    <td class="text-secondary">{{ $sequence->steps_count ?? $sequence->steps->count() }}</td>
                    <td class="text-secondary">{{ $sequence->enrollments_count ?? $sequence->enrollments->count() }}</td>
                    <td>
                        @if($sequence->is_active)
                            <span class="badge bg-green-lt">{{ __('Active') }}</span>
                        @else
                            <span class="badge bg-red-lt">{{ __('Inactive') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" aria-label="{{ __('Actions for :name', ['name' => $sequence->name]) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('sequences.show', $sequence) }}">{{ __('View') }}</a>
                                <a class="dropdown-item" href="{{ route('sequences.edit', $sequence) }}">{{ __('Edit') }}</a>
                                <form method="POST" action="{{ route('sequences.destroy', $sequence) }}" onsubmit="return confirm('{{ __('Delete this sequence?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">{{ __('Delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">
                        <p class="mb-2">{{ __('No sequences found.') }}</p>
                        <a href="{{ route('sequences.create') }}" class="btn btn-outline-primary btn-sm">{{ __('Create your first sequence') }}</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
