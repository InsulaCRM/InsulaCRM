@extends('layouts.app')

@section('title', __('Edit Email Template'))
@section('page-title', __('Edit Email Template'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('email-templates.index') }}">{{ __('Email Templates') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Edit Template') }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Edit Template: :name', ['name' => $template->name]) }}</h3>
                <div class="card-actions">
                    <a href="{{ route('email-templates.index') }}" class="btn btn-ghost-secondary btn-sm">{{ __('Back') }}</a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('email-templates.update', $template->id) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">{{ __('Template Name') }}</label>
                        <input type="text" name="name" class="form-control" required value="{{ $template->name }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Subject Line') }}</label>
                        <input type="text" name="subject" class="form-control" required value="{{ $template->subject }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Body (HTML)') }}</label>
                        <textarea name="body" class="form-control" rows="16" required>{{ $template->body }}</textarea>
                        <small class="form-hint">{{ __('Available variables:') }} @{{name}}, @{{email}}, @{{company}}, @{{date}}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                        <a href="{{ route('email-templates.preview', $template->id) }}" class="btn btn-outline-secondary" target="_blank">{{ __('Preview') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
