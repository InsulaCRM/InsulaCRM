@extends('layouts.auth')

@section('title', __('Install'))

@section('content')
@php
    $selectedMode = session('install.mode');
    $cards = [
        'guided-web' => [
            'title' => __('Guided Web Installer'),
            'summary' => __('Best for shared hosting, managed panels, and buyers following the browser wizard.'),
            'detail' => __('Use the browser installer and fix issues directly in the browser as they appear.'),
        ],
        'vps-server' => [
            'title' => __('Server / SSH Install'),
            'summary' => __('Best for Virtualmin, VPS, and dedicated-server deployments with shell access.'),
            'detail' => __('Use the browser wizard for diagnostics, or use scripts/install.sh and php artisan app:install for a repeatable shell-driven install.'),
        ],
        'local-test' => [
            'title' => __('Local Test Environment'),
            'summary' => __('Best for Docker, XAMPP, and local evaluation environments.'),
            'detail' => __('Use local-only permissions where needed, then tighten ownership and document-root settings before production use.'),
        ],
    ];
@endphp
<div class="card card-md">
    <div class="card-body">
        <h2 class="mb-2 text-center">{{ __('Welcome to InsulaCRM') }}</h2>
        <p class="text-secondary text-center mb-4">{{ __('This wizard will guide you through the installation process.') }}</p>

        @include('install._stepper', ['currentStep' => 1])

        <div class="alert alert-info mb-4">
            <h4 class="alert-title">{{ __('Choose your install path') }}</h4>
            <p class="mb-0">{{ __('This only changes the guidance shown during installation — not the product itself. If unsure, choose Guided Web Installer.') }}</p>
        </div>

        <div class="d-flex flex-column gap-3">
            @foreach($cards as $mode => $card)
                <div class="card {{ $selectedMode === $mode ? 'border-primary' : '' }}">
                    <div class="card-body">
                        <div class="row align-items-center g-3">
                            <div class="col-lg-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h3 class="card-title mb-0">{{ $card['title'] }}</h3>
                                    @if($selectedMode === $mode)
                                        <span class="badge bg-primary-lt ms-2">{{ __('Selected') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <p class="text-secondary mb-1">{{ $card['summary'] }}</p>
                                <p class="text-secondary mb-0" style="font-size: 13px;">{{ $card['detail'] }}</p>
                            </div>
                            <div class="col-lg-4">
                                <form action="{{ route('install.mode') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="mode" value="{{ $mode }}">
                                    <button type="submit" class="btn {{ $selectedMode === $mode ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                        {{ $selectedMode === $mode ? __('Continue With This Path') : __('Use This Install Path') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="alert alert-secondary mt-4 mb-0">
            <h4 class="alert-title">{{ __('Prefer the command line?') }}</h4>
            <p class="mb-2">{{ __('Supported server installs now include a shell helper and a CLI installer command.') }}</p>
            <ul class="mb-0">
                <li><code>scripts/install.sh</code> {{ __('for supported Linux servers with shell access') }}</li>
                <li><code>php artisan app:install</code> {{ __('for a guided CLI install') }}</li>
                <li><code>php artisan system:doctor</code> {{ __('to diagnose environment and runtime issues') }}</li>
            </ul>
        </div>
    </div>
</div>
@endsection
