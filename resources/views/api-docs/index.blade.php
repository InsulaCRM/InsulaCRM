@extends('layouts.app')

@section('title', __('API Documentation'))
@section('page-title', __('API Documentation'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('API Documentation') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Authentication') }}</h3>
            </div>
            <div class="card-body">
                <p>{{ __('All API requests require authentication via an API key. Include your key in the:') }}</p>
                <ul>
                    <li><code>X-API-Key</code> {{ __('request header') }}</li>
                </ul>
                <p class="text-muted small">{{ __('Generate and manage your API key in Settings > API. Query-string authentication is not supported.') }}</p>
                <p><strong>{{ __('Rate Limit') }}:</strong> {{ __('60 requests per minute per API key.') }}</p>
            </div>
        </div>

        @foreach($endpoints as $group)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ $group['group'] }}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th style="width:80px">{{ __('Method') }}</th>
                            <th>{{ __('Endpoint') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Parameters') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group['endpoints'] as $ep)
                        <tr>
                            <td>
                                @php
                                    $colors = ['GET' => 'bg-blue-lt', 'POST' => 'bg-green-lt', 'PUT' => 'bg-orange-lt', 'DELETE' => 'bg-red-lt'];
                                @endphp
                                <span class="badge {{ $colors[$ep['method']] ?? 'bg-secondary-lt' }}">{{ $ep['method'] }}</span>
                            </td>
                            <td><code>{{ $ep['path'] }}</code></td>
                            <td>{{ $ep['description'] }}</td>
                            <td class="text-muted small">{{ is_array($ep['params']) ? implode(', ', $ep['params']) : ($ep['params'] ?: '-') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('Quick Reference') }}</h3>
            </div>
            <div class="card-body">
                <p><strong>{{ __('Base URL') }}:</strong></p>
                <code>{{ url('/api/v1') }}</code>

                <hr>
                <p><strong>{{ __('Response Format') }}:</strong> JSON</p>
                <p><strong>Pagination:</strong></p>
                <pre class="small"><code>{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 25,
    "total": 120
  }
}</code></pre>

                <hr>
                <p><strong>{{ __('Error Response') }}:</strong></p>
                <pre class="small"><code>{
  "error": "Validation failed",
  "details": {
    "field": ["Error message"]
  }
}</code></pre>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('OpenAPI Spec') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">{{ __('Download the OpenAPI 3.0 specification for use with Swagger UI, Postman, or other API tools.') }}</p>
                <a href="{{ route('api-docs.json') }}" class="btn btn-outline-primary w-100" target="_blank">
                    {{ __('Download OpenAPI JSON') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
