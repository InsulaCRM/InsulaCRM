@extends('layouts.app')

@section('title', __('Plugin Management'))
@section('page-title', __('Plugin Management'))

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">{{ __('Plugins') }}</li>
@endsection

@section('content')
<div class="row row-deck row-cards">
    {{-- Upload Plugin --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Upload Plugin') }}</h3>
                <div class="card-actions">
                    <small class="text-secondary">{{ __('Max 50 MB') }}</small>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('plugins.upload') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Plugin ZIP File') }}</label>
                        <input type="file" name="plugin" class="form-control @error('plugin') is-invalid @enderror" accept=".zip" required>
                        @error('plugin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-upload" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                            {{ __('Upload') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Installed Plugins --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Installed Plugins') }}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Version') }}</th>
                            <th>{{ __('Author') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plugins as $plugin)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $plugin->name }}</div>
                                @if($plugin->description)
                                <div class="text-secondary small">{{ Str::limit($plugin->description, 80) }}</div>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $plugin->version ?? '-' }}</td>
                            <td class="text-secondary">{{ $plugin->author ?? '-' }}</td>
                            <td>
                                @if($plugin->is_active)
                                    <span class="badge bg-green-lt">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-red-lt">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <form method="POST" action="{{ route('plugins.toggle', $plugin) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $plugin->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                            {{ $plugin->is_active ? __('Disable') : __('Enable') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('plugins.uninstall', $plugin) }}" onsubmit="return confirm('{{ __('Are you sure you want to uninstall the plugin \':name\'? This action cannot be undone.', ['name' => $plugin->name]) }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            {{ __('Uninstall') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                {{ __('No plugins installed yet. Upload a plugin ZIP to get started.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
