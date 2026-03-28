@extends('layouts.app')
@section('title', __('Knowledge Base'))
@section('page-title', __('Knowledge Base'))

@section('content')
<div class="container-xl">
    {{-- Hero search --}}
    <div class="card bg-primary text-white mb-4">
        <div class="card-body text-center py-5">
            <h1 class="mb-2" style="font-size: 1.75rem; font-weight: 700;">{{ __('How can we help you?') }}</h1>
            <p class="mb-4 opacity-75">{{ __('Search our knowledge base or browse categories below.') }}</p>
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <form method="GET" action="{{ route('help.index') }}">
                        <div class="input-icon">
                            <span class="input-icon-addon" style="color: #999;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
                            </span>
                            <input type="text" name="q" class="form-control form-control-lg" placeholder="{{ __('Search articles...') }}" value="{{ $search ?? '' }}" style="color: #1e293b;">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($search)
        <div class="mb-3 d-flex align-items-center">
            <span class="text-muted">{{ __('Showing results for') }}: <strong>{{ $search }}</strong></span>
            <a href="{{ route('help.index') }}" class="btn btn-sm btn-outline-secondary ms-2">{{ __('Clear') }}</a>
        </div>
    @endif

    @forelse($articles as $category)
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <span class="avatar avatar-sm bg-primary-lt text-primary me-2">
                    @switch($category['icon'])
                        @case('rocket')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 13a8 8 0 0 1 7 7a6 6 0 0 0 3 -5a9 9 0 0 0 6 -8a3 3 0 0 0 -3 -3a9 9 0 0 0 -8 6a6 6 0 0 0 -5 3"/><path d="M7 14a6 6 0 0 0 -3 6a6 6 0 0 0 6 -3"/><circle cx="15" cy="9" r="1"/></svg>
                            @break
                        @case('users')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                            @break
                        @case('building')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M5 21v-14l8 -4v18"/><path d="M19 21v-10l-6 -4"/><path d="M9 9l0 .01"/><path d="M9 12l0 .01"/><path d="M9 15l0 .01"/></svg>
                            @break
                        @case('layout-kanban')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="4" x2="10" y2="4"/><line x1="14" y1="4" x2="20" y2="4"/><rect x="4" y="8" width="6" height="12" rx="2"/><rect x="14" y="8" width="6" height="8" rx="2"/></svg>
                            @break
                        @case('message-circle')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 20l1.3 -3.9a9 8 0 1 1 3.4 2.9l-4.7 1"/></svg>
                            @break
                        @case('calendar')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/></svg>
                            @break
                        @case('chart-bar')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="3" y="12" width="6" height="8" rx="1"/><rect x="9" y="8" width="6" height="12" rx="1"/><rect x="15" y="4" width="6" height="16" rx="1"/></svg>
                            @break
                        @case('settings')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                            @break
                        @case('plug')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h10v6a3 3 0 0 1 -3 3h-4a3 3 0 0 1 -3 -3z"/><line x1="9" y1="3" x2="9" y2="7"/><line x1="15" y1="3" x2="15" y2="7"/><path d="M12 16v2a2 2 0 0 0 2 2h0a2 2 0 0 0 2 -2"/></svg>
                            @break
                        @case('bug')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 9v-1a3 3 0 0 1 6 0v1"/><path d="M8 9h8a6 6 0 0 1 1 3v3a5 5 0 0 1 -10 0v-3a6 6 0 0 1 1 -3"/><line x1="3" y1="13" x2="7" y2="13"/><line x1="17" y1="13" x2="21" y2="13"/><line x1="12" y1="20" x2="12" y2="14"/><line x1="4" y1="19" x2="7.35" y2="17"/><line x1="20" y1="19" x2="16.65" y2="17"/><line x1="4" y1="7" x2="7.75" y2="9.4"/><line x1="20" y1="7" x2="16.25" y2="9.4"/></svg>
                            @break
                    @endswitch
                </span>
                <h3 class="mb-0">{{ $category['name'] }}</h3>
                <span class="badge bg-secondary-lt ms-2">{{ count($category['articles']) }}</span>
            </div>
            <div class="row row-cards">
                @foreach($category['articles'] as $article)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('help.show', $article['slug']) }}" class="card card-link card-link-pop h-100">
                            <div class="card-body">
                                <h4 class="card-title mb-2" style="font-size: 0.95rem;">{{ $article['title'] }}</h4>
                                <p class="text-muted small mb-0">{{ Str::limit(strip_tags($article['summary'] ?? $article['body']), 100) }}</p>
                            </div>
                            @if(!empty($article['tags']))
                            <div class="card-footer py-2">
                                @foreach(array_slice($article['tags'], 0, 3) as $tag)
                                    <span class="badge bg-blue-lt me-1" style="font-size: 0.7rem;">{{ $tag }}</span>
                                @endforeach
                            </div>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="empty">
            <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
            </div>
            <p class="empty-title">{{ __('No articles found') }}</p>
            <p class="empty-subtitle text-muted">{{ __('Try a different search term or browse all categories.') }}</p>
            <div class="empty-action">
                <a href="{{ route('help.index') }}" class="btn btn-primary">{{ __('View All Articles') }}</a>
            </div>
        </div>
    @endforelse
</div>
@endsection
