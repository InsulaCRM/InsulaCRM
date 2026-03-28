@extends('layouts.app')

@section('title', __('Email Templates'))
@section('page-title', __('Email Templates'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Email Templates') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Email Templates') }}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Subject') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td class="text-muted">{{ $template->subject }}</td>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($template->updated_at)->diffForHumans() }}</td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('email-templates.edit', $template->id) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    <a href="{{ route('email-templates.preview', $template->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank">{{ __('Preview') }}</a>
                                    <form method="POST" action="{{ route('email-templates.destroy', $template->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this template?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">{{ __('No email templates yet.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Create Template') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('email-templates.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Template Name') }}</label>
                        <input type="text" name="name" class="form-control" required placeholder="{{ __('e.g., Welcome Email') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Subject Line') }}</label>
                        <input type="text" name="subject" class="form-control" required placeholder="{{ __('e.g., Welcome to our service') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Body (HTML)') }}</label>
                        <textarea name="body" class="form-control" rows="8" required placeholder="{{ __('Enter HTML email body...') }}"></textarea>
                        <small class="form-hint">{{ __('Available variables:') }} @{{name}}, @{{email}}, @{{company}}, @{{date}}</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('Create Template') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
