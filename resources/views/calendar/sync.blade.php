@extends('layouts.app')

@section('title', __('Calendar Sync'))
@section('page-title', __('Calendar Sync'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('calendar.index') }}">{{ __('Calendar') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Sync Settings') }}</li>
@endsection

@section('content')
<div class="row row-deck row-cards">

    {{-- iCal Feed Section --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('iCal Calendar Feed') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    {{ __('Generate a private iCal feed URL to subscribe to your InsulaCRM tasks and activities in your favorite calendar app.') }}
                </p>

                @if($hasToken && $feedUrl)
                    <div class="mb-3">
                        <label class="form-label">{{ __('Your Feed URL') }}</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="feed-url" value="{{ $feedUrl }}" readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="copyFeedUrl()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-copy" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z"/>
                                    <path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/>
                                </svg>
                                {{ __('Copy') }}
                            </button>
                        </div>
                        <small class="form-hint text-warning">
                            {{ __('Keep this URL private. Anyone with this link can view your calendar.') }}
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <form action="{{ route('calendar.sync.generate') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning" onclick="return confirm('{{ __('This will invalidate your current feed URL. Continue?') }}')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                    <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                </svg>
                                {{ __('Regenerate URL') }}
                            </button>
                        </form>

                        <form action="{{ route('calendar.sync.disconnect') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('{{ __('This will remove your calendar feed. Subscribed calendars will stop updating. Continue?') }}')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-unlink" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5"/>
                                    <path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5"/>
                                    <line x1="16" y1="21" x2="16" y2="19"/>
                                    <line x1="19" y1="16" x2="21" y2="16"/>
                                    <line x1="3" y1="8" x2="5" y2="8"/>
                                    <line x1="8" y1="3" x2="8" y2="5"/>
                                </svg>
                                {{ __('Disconnect') }}
                            </button>
                        </form>
                    </div>
                @else
                    <form action="{{ route('calendar.sync.generate') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12.5 21h-6.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v5"/>
                                <path d="M16 3v4"/>
                                <path d="M8 3v4"/>
                                <path d="M4 11h16"/>
                                <path d="M16 19h6"/>
                                <path d="M19 16v6"/>
                            </svg>
                            {{ __('Generate Feed URL') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Instructions Section --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('How to Subscribe') }}</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h4 class="mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-google" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M17.788 5.108a9 9 0 1 0 3.212 6.892h-8"/>
                        </svg>
                        {{ __('Google Calendar') }}
                    </h4>
                    <ol class="ps-3 mb-0">
                        <li class="mb-1">{{ __('Open Google Calendar') }}</li>
                        <li class="mb-1">{{ __('Click "Other calendars" (+) on the left sidebar') }}</li>
                        <li class="mb-1">{{ __('Select "From URL"') }}</li>
                        <li>{{ __('Paste your feed URL and click "Add calendar"') }}</li>
                    </ol>
                </div>

                <div>
                    <h4 class="mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mail" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/>
                            <path d="M3 7l9 6l9 -6"/>
                        </svg>
                        {{ __('Microsoft Outlook') }}
                    </h4>
                    <ol class="ps-3 mb-0">
                        <li class="mb-1">{{ __('Open Outlook Calendar') }}</li>
                        <li class="mb-1">{{ __('Click "Add calendar"') }}</li>
                        <li class="mb-1">{{ __('Select "Subscribe from web"') }}</li>
                        <li>{{ __('Paste your feed URL and click "Import"') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Import External Calendar Section --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Import External Calendar') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    {{ __('Import events from an external iCal (.ics) URL. Each event will be created as a task in InsulaCRM.') }}
                </p>

                <form action="{{ route('calendar.sync.import') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="ical_url">{{ __('iCal Feed URL') }}</label>
                        <input type="url"
                               class="form-control @error('ical_url') is-invalid @enderror"
                               id="ical_url"
                               name="ical_url"
                               placeholder="https://calendar.google.com/calendar/ical/..."
                               value="{{ old('ical_url') }}"
                               required>
                        @error('ical_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                            <path d="M7 11l5 5l5 -5"/>
                            <path d="M12 4l0 12"/>
                        </svg>
                        {{ __('Import Events') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function copyFeedUrl() {
    var input = document.getElementById('feed-url');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        var btn = input.nextElementSibling;
        var originalText = btn.innerHTML;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg> {{ __("Copied!") }}';
        setTimeout(function() {
            btn.innerHTML = originalText;
        }, 2000);
    });
}
</script>
@endpush
