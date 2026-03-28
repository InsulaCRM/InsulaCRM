@extends('layouts.app')

@section('title', __('Getting Started'))
@section('page-title', __('Getting Started'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Welcome to :app!', ['app' => config('app.name')]) }}</h3>
                <div class="card-actions">
                    <a href="{{ route('onboarding.skip') }}" class="btn btn-ghost-secondary btn-sm">{{ __('Skip Setup') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="steps steps-counter steps-green mb-4">
                    <a href="#" class="step-item {{ $step >= 1 ? 'active' : '' }}">{{ __('Company Profile') }}</a>
                    <a href="#" class="step-item {{ $step >= 2 ? 'active' : '' }}">{{ __('Invite Team') }}</a>
                    <a href="#" class="step-item {{ $step >= 3 ? 'active' : '' }}">{{ __('First Lead') }}</a>
                    <a href="#" class="step-item {{ $step >= 4 ? 'active' : '' }}">{{ __('All Done') }}</a>
                </div>

                @if($step === 1)
                <div class="text-center py-4">
                    <h2>{{ __('Set Up Your Company Profile') }}</h2>
                    <p class="text-muted">{{ __('Configure your company name, timezone, and branding.') }}</p>
                    <a href="{{ route('settings.index') }}" class="btn btn-primary mt-3">
                        {{ __('Go to Settings') }}
                    </a>
                </div>
                @elseif($step === 2)
                <div class="text-center py-4">
                    <h2>{{ __('Invite Your Team') }}</h2>
                    <p class="text-muted">{{ __('Add agents and team members to start collaborating.') }}</p>
                    <a href="{{ route('settings.index') }}#team" class="btn btn-primary mt-3">
                        {{ __('Invite Team Members') }}
                    </a>
                    <div class="mt-3">
                        <a href="{{ route('onboarding.skip') }}" class="text-muted small">{{ __('I\'ll do this later') }}</a>
                    </div>
                </div>
                @elseif($step === 3)
                <div class="text-center py-4">
                    <h2>{{ __('Create Your First Lead') }}</h2>
                    <p class="text-muted">{{ __('Add a lead to get started with your pipeline.') }}</p>
                    <a href="{{ route('leads.create') }}" class="btn btn-primary mt-3">
                        {{ __('Create Lead') }}
                    </a>
                    <div class="mt-3">
                        <a href="{{ route('onboarding.skip') }}" class="text-muted small">{{ __('I\'ll do this later') }}</a>
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-green mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                    <h2>{{ __('You\'re All Set!') }}</h2>
                    <p class="text-muted">{{ __('Your CRM is ready to use. Start managing your leads and deals.') }}</p>
                    <form method="POST" action="{{ route('onboarding.complete') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg mt-3">
                            {{ __('Go to Dashboard') }}
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
