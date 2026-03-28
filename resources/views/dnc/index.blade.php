@extends('layouts.app')

@section('title', __('Do Not Contact List'))
@section('page-title', __('Do Not Contact List'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Do Not Contact') }}</li>
@endsection

@section('content')
<div class="row row-deck row-cards">
    {{-- Add Single Entry --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Add Entry') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dnc.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Phone') }}</label>
                        <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('Phone number') }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('Email address') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Reason') }}</label>
                        <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" placeholder="{{ __('Reason') }}">
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Add to DNC') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- CSV Import --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Import CSV') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dnc.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv" required>
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">{{ __('Import CSV') }}</button>
                </form>
            </div>
        </div>
    </div>

    {{-- DNC Table --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('DNC Entries') }}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Reason') }}</th>
                            <th>{{ __('Added By') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry->phone ?? '-' }}</td>
                            <td>{{ $entry->email ?? '-' }}</td>
                            <td class="text-secondary">{{ $entry->reason ?? '-' }}</td>
                            <td class="text-secondary">{{ $entry->addedByUser->name ?? '-' }}</td>
                            <td class="text-secondary">{{ $entry->added_at ? $entry->added_at->format('M d, Y') : $entry->created_at->format('M d, Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('dnc.destroy', $entry) }}" onsubmit="return confirm('{{ __('Remove this DNC entry?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost-danger btn-sm">{{ __('Remove') }}</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary">{{ __('No DNC entries found.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-secondary">{{ __('Showing') }} <span>{{ $entries->firstItem() ?? 0 }}</span> {{ __('to') }} <span>{{ $entries->lastItem() ?? 0 }}</span> {{ __('of') }} <span>{{ $entries->total() }}</span> {{ __('entries') }}</p>
                <div class="ms-auto">
                    {{ $entries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
