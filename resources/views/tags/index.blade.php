@extends('layouts.app')

@section('title', __('Tags'))
@section('page-title', __('Tag Management'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Tags') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Create Tag') }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('tags.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label required">{{ __('Name') }}</label>
                        <input type="text" name="name" class="form-control" required maxlength="50" placeholder="{{ __('e.g. Hot Market, VIP, Cash Only') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">{{ __('Color') }}</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['blue','green','red','orange','purple','cyan','yellow','pink','teal'] as $c)
                            <label class="form-colorinput">
                                <input type="radio" name="color" value="{{ $c }}" class="form-colorinput-input" {{ $c === 'blue' ? 'checked' : '' }}>
                                <span class="form-colorinput-color bg-{{ $c }}"></span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('Create Tag') }}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('All Tags') }}</h3>
            </div>
            @if($tags->count())
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('Tag') }}</th>
                            <th>{{ __('Leads') }}</th>
                            <th>{{ $modeTerms['deal_label'] ?? __('Deals') }}s</th>
                            <th>{{ __('Created') }}</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tags as $tag)
                        <tr>
                            <td><span class="badge bg-{{ $tag->color }}-lt">{{ $tag->name }}</span></td>
                            <td>{{ $tag->leads_count }}</td>
                            <td>{{ $tag->deals_count }}</td>
                            <td>{{ $tag->created_at->format('M d, Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('tags.destroy', $tag) }}" onsubmit="return confirm('{{ __('Delete this tag?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost-danger btn-sm btn-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="card-body text-center text-muted py-4">
                {{ __('No tags created yet.') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
