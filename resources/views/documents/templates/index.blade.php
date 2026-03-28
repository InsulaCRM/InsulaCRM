@extends('layouts.app')

@section('title', __('Document Templates'))
@section('page-title', __('Document Templates'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('pipeline') }}">{{ __('Pipeline') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Document Templates') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Document Templates') }}</h3>
        <div class="card-actions">
            <a href="{{ route('document-templates.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Create Template') }}
            </a>
        </div>
    </div>
    @if($templates->count())
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th class="text-center">{{ __('Default') }}</th>
                    <th class="text-center">{{ __('Documents Generated') }}</th>
                    <th class="text-center">{{ __('Created') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $template)
                <tr>
                    <td>
                        <div class="font-weight-medium">{{ $template->name }}</div>
                    </td>
                    <td>
                        @php
                            $typeBadges = [
                                'loi' => 'bg-blue-lt',
                                'purchase_agreement' => 'bg-green-lt',
                                'assignment_contract' => 'bg-purple-lt',
                                'addendum' => 'bg-yellow-lt',
                                'other' => 'bg-secondary-lt',
                            ];
                        @endphp
                        <span class="badge {{ $typeBadges[$template->type] ?? 'bg-secondary-lt' }}">
                            {{ \App\Models\DocumentTemplate::typeLabel($template->type) }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($template->is_default)
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-green" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary-lt">{{ $template->generated_documents_count }}</span>
                    </td>
                    <td class="text-center text-secondary">
                        {{ $template->created_at->format('M d, Y') }}
                    </td>
                    <td class="text-end">
                        <a href="{{ route('document-templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/><path d="M16 5l3 3"/></svg>
                            {{ __('Edit') }}
                        </a>
                        <form method="POST" action="{{ route('document-templates.destroy', $template) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this template?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="card-body">
        <div class="empty">
            <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><line x1="9" y1="9" x2="10" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
            </div>
            <p class="empty-title">{{ __('No document templates yet') }}</p>
            <p class="empty-subtitle text-secondary">
                {{ __('Create your first template to start generating offer documents, contracts, and letters.') }}
            </p>
            <div class="empty-action">
                <a href="{{ route('document-templates.create') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Create Template') }}
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
