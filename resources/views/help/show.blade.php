@extends('layouts.app')
@section('title', $article['title'])
@section('page-title', __('Knowledge Base'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('help.index') }}">{{ __('Help') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $article['title'] ?? __('Article') }}</li>
@endsection

@section('content')
<div class="container-xl">
    <div class="row">
        {{-- Sidebar navigation --}}
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card sticky-top" style="top: 1rem;">
                <div class="card-header">
                    <h4 class="card-title">{{ $article['category'] }}</h4>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($siblings as $sibling)
                        <a href="{{ route('help.show', $sibling['slug']) }}" class="list-group-item list-group-item-action {{ $sibling['slug'] === $article['slug'] ? 'active' : '' }}" style="font-size: 0.85rem;">
                            {{ $sibling['title'] }}
                        </a>
                    @endforeach
                </div>
                <div class="card-footer">
                    <a href="{{ route('help.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="12" x2="11" y2="18"/><line x1="5" y1="12" x2="11" y2="6"/></svg>
                        {{ __('All Categories') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Article content --}}
        <div class="col-lg-9">
            <div class="d-lg-none mb-3">
                <a href="{{ route('help.index') }}" class="btn btn-outline-secondary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="5" y1="12" x2="11" y2="18"/><line x1="5" y1="12" x2="11" y2="6"/></svg>
                    {{ __('Back to Knowledge Base') }}
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="text-muted small mb-1">{{ $article['category'] }}</div>
                        <h2 class="card-title mb-0">{{ $article['title'] }}</h2>
                    </div>
                </div>
                {{-- Safe: article body is hardcoded in KnowledgeBaseController, not user input --}}
                <div class="card-body kb-article">
                    {!! $article['body'] !!}
                </div>
                @if(!empty($article['tags']))
                <div class="card-footer">
                    <span class="text-muted small me-2">{{ __('Related topics:') }}</span>
                    @foreach($article['tags'] as $tag)
                        <a href="{{ route('help.index', ['q' => $tag]) }}" class="badge bg-blue-lt me-1 text-decoration-none">{{ $tag }}</a>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Previous / Next navigation --}}
            @if($prev || $next)
            <div class="row mt-3">
                <div class="col-6">
                    @if($prev)
                    <a href="{{ route('help.show', $prev['slug']) }}" class="card card-link h-100">
                        <div class="card-body py-3">
                            <div class="text-muted small mb-1">{{ __('Previous') }}</div>
                            <span style="font-size: 0.9rem;">{{ $prev['title'] }}</span>
                        </div>
                    </a>
                    @endif
                </div>
                <div class="col-6">
                    @if($next)
                    <a href="{{ route('help.show', $next['slug']) }}" class="card card-link h-100">
                        <div class="card-body py-3 text-end">
                            <div class="text-muted small mb-1">{{ __('Next') }}</div>
                            <span style="font-size: 0.9rem;">{{ $next['title'] }}</span>
                        </div>
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .kb-article { line-height: 1.8; font-size: 0.925rem; }
    .kb-article h3 { font-size: 1.15rem; font-weight: 600; margin-top: 2rem; margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid rgba(98,105,118,.16); }
    .kb-article h4 { font-size: 1rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; }
    .kb-article p { margin-bottom: 0.75rem; }
    .kb-article ul, .kb-article ol { margin-bottom: 1rem; padding-left: 1.5rem; }
    .kb-article li { margin-bottom: 0.35rem; }
    .kb-article code { background: rgba(98,105,118,.08); padding: 0.15em 0.4em; border-radius: 4px; font-size: 0.85em; }
    .kb-article table { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
    .kb-article table th, .kb-article table td { padding: 0.5rem 0.75rem; border: 1px solid rgba(98,105,118,.16); text-align: left; font-size: 0.875rem; }
    .kb-article table th { background: rgba(98,105,118,.06); font-weight: 600; }
    .kb-article table tr:hover td { background: rgba(98,105,118,.03); }
    .kb-article .alert { font-size: 0.875rem; }
    .kb-article .kb-step { display: flex; gap: 0.75rem; margin-bottom: 1rem; }
    .kb-article .kb-step-num { flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%; background: var(--tblr-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; margin-top: 0.1rem; }
    .kb-article .kb-step-content { flex: 1; }
    .kb-article .kb-callout { border-left: 3px solid var(--tblr-primary); padding: 0.75rem 1rem; background: rgba(32,107,196,.04); border-radius: 0 4px 4px 0; margin-bottom: 1rem; }
    .kb-article .kb-callout-warning { border-left-color: var(--tblr-warning); background: rgba(247,191,6,.04); }
    .kb-article .kb-callout-success { border-left-color: var(--tblr-success); background: rgba(47,179,68,.04); }
    .kb-article hr { margin: 1.5rem 0; border-color: rgba(98,105,118,.16); }
</style>
@endpush
