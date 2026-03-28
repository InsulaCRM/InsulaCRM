<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Dashboard')) - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler-vendors.min.css">
    <style>
        .navbar-vertical { scrollbar-width: none; }
        .navbar-vertical::-webkit-scrollbar { display: none; }
        .navbar-vertical .navbar-collapse { scrollbar-width: none; }
        .navbar-vertical .navbar-collapse::-webkit-scrollbar { display: none; }
        /* Ensure solid-bg badges always have white readable text */
        .badge.bg-secondary, .badge.bg-primary, .badge.bg-success, .badge.bg-danger,
        .badge.bg-warning, .badge.bg-info, .badge.bg-dark,
        .badge.bg-green, .badge.bg-yellow, .badge.bg-red, .badge.bg-blue,
        .badge.bg-purple, .badge.bg-orange, .badge.bg-cyan, .badge.bg-azure,
        .badge.bg-teal, .badge.bg-pink, .badge.bg-indigo, .badge.bg-lime {
            color: #fff !important;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/mobile.css') }}">
    @include('layouts._pwa')
    @stack('styles')
    <script>
    // Render AI markdown to HTML — shared across all AI output areas
    window.renderAiMarkdown = function(text) {
        if (!text) return '';
        // Escape HTML entities first
        var div = document.createElement('div');
        div.textContent = text;
        var s = div.innerHTML;
        // Headers: **Header:** at line start → bold header
        s = s.replace(/^(\*\*[^*]+\*\*:?)\s*$/gm, function(m, h) {
            var inner = h.replace(/^\*\*|\*\*:?$/g, '');
            return '<strong class="d-block mt-3 mb-1" style="font-size:1.05em;">' + inner + '</strong>';
        });
        // Bold: **text**
        s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        // Italic: *text*
        s = s.replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>');
        // Bullet list items: lines starting with - or *
        s = s.replace(/^[\s]*[-*]\s+(.+)$/gm, '<li style="margin-left:1rem;margin-bottom:0.25rem;">$1</li>');
        // Numbered list items: 1. text
        s = s.replace(/^[\s]*\d+\.\s+(.+)$/gm, '<li style="margin-left:1rem;margin-bottom:0.25rem;">$1</li>');
        // Wrap consecutive <li> in <ul>
        s = s.replace(/((?:<li[^>]*>.*?<\/li>\s*)+)/g, '<ul style="list-style:none;padding-left:0;margin:0.25rem 0;">$1</ul>');
        // Line breaks for remaining newlines (but not inside tags)
        s = s.replace(/\n/g, '<br>');
        // Clean up excessive <br> next to block elements
        s = s.replace(/<br>\s*(<strong class="d-block)/g, '$1');
        s = s.replace(/<br>\s*(<ul)/g, '$1');
        s = s.replace(/(<\/ul>)\s*<br>/g, '$1');
        return s;
    };

    // Recently viewed — defined in <head> so it's available to all page scripts
    window.trackRecentlyViewed = function(type, id, name, url) {
        var KEY = 'insulacrm_recently_viewed';
        var items = [];
        try { items = JSON.parse(localStorage.getItem(KEY)) || []; } catch(e) {}
        items = items.filter(function(i) { return !(i.type === type && i.id === id); });
        items.unshift({ type: type, id: id, name: name, url: url, time: Date.now() });
        if (items.length > 8) items = items.slice(0, 8);
        localStorage.setItem(KEY, JSON.stringify(items));
        // Live-update the dropdown if already rendered
        window._refreshRecentlyViewed && window._refreshRecentlyViewed();
    };
    </script>
</head>
<body class="layout-fluid" data-bs-theme="{{ auth()->check() && auth()->user()->theme === 'dark' ? 'dark' : 'light' }}">
    <a href="#main-content" class="visually-hidden-focusable">{{ __('Skip to main content') }}</a>
    @if(session('impersonating'))
    <div class="alert alert-warning text-center mb-0 rounded-0">
        {{ __('You are impersonating') }} <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->tenant->name }}).
        <a href="{{ route('settings.stopImpersonation') }}" class="alert-link">{{ __('Stop Impersonation') }}</a>
    </div>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js" defer></script>
    <div class="page">
        <!-- Sidebar -->
        <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark" aria-label="{{ __('Main navigation') }}">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark" style="margin-bottom: 0;">
                    <a href="{{ route('dashboard') }}">
                        @php $tenantLogo = auth()->user()->tenant->logo_path ?? null; @endphp
                        @if($tenantLogo)
                            <img src="{{ asset('storage/' . $tenantLogo) }}" alt="{{ config('app.name') }}" style="max-height: 56px; max-width: 220px;">
                        @else
                            <img src="{{ asset('images/logo-white.png') }}" alt="{{ config('app.name') }}" style="max-height: 84px; max-width: 330px;">
                        @endif
                    </a>
                </h1>
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-1" role="list">

                        {{-- ── CRM ─────────────────────────────────── --}}
                        <li class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('dashboard') }}" {{ request()->is('dashboard') ? 'aria-current=page' : '' }}>
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Dashboard') }}</span>
                            </a>
                        </li>

                        @if(auth()->user()->canManageLeads())
                        <li class="nav-item {{ request()->is('leads*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('leads.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Leads') }}</span>
                            </a>
                        </li>
                        @endif

                        @if(($businessMode ?? 'wholesale') === 'realestate')
                        <li class="nav-item {{ request()->is('listings*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('listings.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M9 8l1 0"/><path d="M9 12l1 0"/><path d="M9 16l1 0"/><path d="M14 8l1 0"/><path d="M14 12l1 0"/><path d="M14 16l1 0"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Listings') }}</span>
                            </a>
                        </li>
                        @endif

                        @unless(auth()->user()->isDispositionAgent())
                        <li class="nav-item {{ request()->is('properties*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('properties.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M5 21v-14l8 -4v18"/><path d="M19 21v-10l-6 -4"/><path d="M9 9l0 .01"/><path d="M9 12l0 .01"/><path d="M9 15l0 .01"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Properties') }}</span>
                            </a>
                        </li>
                        @endunless

                        @if(($businessMode ?? 'wholesale') === 'realestate')
                        <li class="nav-item {{ request()->is('showings*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('showings.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Showings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->is('open-houses*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('open-houses.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Open Houses') }}</span>
                            </a>
                        </li>
                        @endif

                        @unless(auth()->user()->isFieldScout())
                        <li class="nav-item {{ request()->is('pipeline*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('pipeline') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>
                                </span>
                                <span class="nav-link-title">{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Transactions') : __('Pipeline') }}</span>
                            </a>
                        </li>
                        @endunless

                        @if(auth()->user()->canManageBuyers())
                        <li class="nav-item {{ request()->is('buyers*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('buyers.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12"/><path d="M20 12v4h-4a2 2 0 0 1 0 -4h4"/></svg>
                                </span>
                                <span class="nav-link-title">{{ $modeTerms['buyer_label'] ?? __('Buyers') }}</span>
                            </a>
                        </li>
                        @endif

                        @unless(auth()->user()->isFieldScout())
                        <li class="nav-item {{ request()->is('calendar*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('calendar.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/><line x1="11" y1="15" x2="12" y2="15"/><line x1="12" y1="15" x2="12" y2="18"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Calendar') }}</span>
                            </a>
                        </li>
                        @endunless

                        @unless(auth()->user()->isFieldScout())
                        <li class="nav-item {{ request()->is('activities*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('activities.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h4l3 8l4 -16l3 8h4"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Activity Feed') }}</span>
                            </a>
                        </li>
                        @endunless

                        @if(auth()->user()->isAdmin())
                        {{-- ── MARKETING ───────────────────────────── --}}
                        @php $marketingActive = request()->is('sequences*') || request()->is('lists*') || request()->is('campaigns*') || request()->is('workflows*') || request()->is('goals*') || request()->is('tags*') || request()->is('document-templates*'); @endphp
                        <li class="nav-item dropdown {{ $marketingActive ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#sidebar-marketing" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ $marketingActive ? 'true' : 'false' }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 8a3 3 0 0 1 0 6"/><path d="M10 8v11a1 1 0 0 1 -1 1h-1a1 1 0 0 1 -1 -1v-5"/><path d="M12 8h0l4.524 -3.77a.9 .9 0 0 1 1.476 .692v12.156a.9 .9 0 0 1 -1.476 .692l-4.524 -3.77h-8a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h8"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Marketing') }}</span>
                            </a>
                            <div class="dropdown-menu {{ $marketingActive ? 'show' : '' }}">

                                <a class="dropdown-item {{ request()->is('sequences*') ? 'active' : '' }}" href="{{ route('sequences.index') }}">{{ __('Sequences') }}</a>
                                <a class="dropdown-item {{ request()->is('lists*') ? 'active' : '' }}" href="{{ route('lists.index') }}">{{ __('Lists') }}</a>
                                <a class="dropdown-item {{ request()->is('campaigns*') ? 'active' : '' }}" href="{{ route('campaigns.index') }}">{{ __('Campaigns') }}</a>
                                <a class="dropdown-item {{ request()->is('workflows*') ? 'active' : '' }}" href="{{ route('workflows.index') }}">{{ __('Workflows') }}</a>
                                <a class="dropdown-item {{ request()->is('goals*') ? 'active' : '' }}" href="{{ route('goals.index') }}">{{ __('Goals') }}</a>
                                <a class="dropdown-item {{ request()->is('tags*') ? 'active' : '' }}" href="{{ route('tags.index') }}">{{ __('Tags') }}</a>
                                <a class="dropdown-item {{ request()->is('document-templates*') ? 'active' : '' }}" href="{{ route('document-templates.index') }}">{{ __('Documents') }}</a>
                            </div>
                        </li>

                        {{-- ── INSIGHTS ────────────────────────────── --}}
                        @php $insightsActive = request()->is('reports*') || request()->is('audit-log*') || request()->is('ai-history*'); @endphp
                        <li class="nav-item dropdown {{ $insightsActive ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#sidebar-insights" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ $insightsActive ? 'true' : 'false' }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="19" x2="20" y2="19"/><polyline points="4 15 8 9 12 11 16 6 20 10"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Insights') }}</span>
                            </a>
                            <div class="dropdown-menu {{ $insightsActive ? 'show' : '' }}">

                                <a class="dropdown-item {{ request()->is('reports*') ? 'active' : '' }}" href="{{ route('reports.index') }}">{{ __('Reports') }}</a>
                                <a class="dropdown-item {{ request()->is('audit-log*') ? 'active' : '' }}" href="{{ route('audit-log.index') }}">{{ __('Audit Log') }}</a>
                                <a class="dropdown-item {{ request()->is('ai-history*') ? 'active' : '' }}" href="{{ route('ai-log.index') }}">{{ __('AI History') }}</a>
                            </div>
                        </li>

                        {{-- ── SYSTEM ──────────────────────────────── --}}
                        @php $systemActive = request()->is('settings*') || request()->is('api-docs*') || request()->is('error-logs*'); @endphp
                        <li class="nav-item dropdown {{ $systemActive ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#sidebar-system" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ $systemActive ? 'true' : 'false' }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('System') }}</span>
                            </a>
                            <div class="dropdown-menu {{ $systemActive ? 'show' : '' }}">

                                <a class="dropdown-item {{ request()->is('settings*') && !request()->is('settings/plugins*') ? 'active' : '' }}" href="{{ route('settings.index') }}">{{ __('Settings') }}</a>
                                <a class="dropdown-item {{ request()->is('settings/plugins*') ? 'active' : '' }}" href="{{ route('plugins.index') }}">{{ __('Plugins') }}</a>
                                <a class="dropdown-item {{ request()->is('api-docs*') ? 'active' : '' }}" href="{{ route('api-docs.index') }}">{{ __('API Docs') }}</a>
                                <a class="dropdown-item {{ request()->is('error-logs*') ? 'active' : '' }}" href="{{ route('error-logs.index') }}">{{ __('Bug Reports') }}</a>
                            </div>
                        </li>
                        @endif

                        {{-- ── Plugin menu items ──────────────────── --}}
                        @php
                            $pluginMenuItems = app(\App\Services\HookManager::class)->getMenuItems();
                        @endphp
                        @if(count($pluginMenuItems) > 0)
                        <li class="nav-label mt-3 px-3"><small class="text-uppercase text-secondary fw-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">{{ __('Plugins') }}</small></li>
                        @foreach($pluginMenuItems as $menuItem)
                        <li class="nav-item {{ request()->is(ltrim($menuItem['route'], '/') . '*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ url($menuItem['route']) }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h10v6a3 3 0 0 1 -3 3h-4a3 3 0 0 1 -3 -3z"/><line x1="9" y1="3" x2="9" y2="7"/><line x1="15" y1="3" x2="15" y2="7"/><path d="M12 16v2a2 2 0 0 0 2 2h0a2 2 0 0 0 2 -2"/></svg>
                                </span>
                                <span class="nav-link-title">{{ $menuItem['label'] }}</span>
                            </a>
                        </li>
                        @endforeach
                        @endif

                        {{-- ── HELP (always visible) ──────────────── --}}
                        <li class="nav-label mt-3 px-3"><hr class="my-0" style="border-color: rgba(255,255,255,0.1);"></li>
                        <li class="nav-item {{ request()->is('help*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('help.index') }}">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="17" x2="12" y2="17.01"/><path d="M12 13.5a1.5 1.5 0 0 1 1 -1.5a2.6 2.6 0 1 0 -3 -4"/></svg>
                                </span>
                                <span class="nav-link-title">{{ __('Help') }}</span>
                            </a>
                        </li>
                    </ul>
                    {{-- ── Mode Indicator ──────────────────────── --}}
                    <div class="px-3 py-2 mt-2" style="border-top: 1px solid rgba(255,255,255,0.08);">
                        @php $isRE = ($businessMode ?? 'wholesale') === 'realestate'; @endphp
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-xs {{ $isRE ? 'bg-teal' : 'bg-blue' }} text-white" style="width: 22px; height: 22px; font-size: 0.6rem;">
                                @if($isRE)
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M5 21v-14l8 -4v18"/><path d="M19 21v-10l-6 -4"/><path d="M9 9l0 .01"/><path d="M9 12l0 .01"/><path d="M9 15l0 .01"/><path d="M9 18l0 .01"/></svg>
                                @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M9 8l1 0"/><path d="M9 12l1 0"/><path d="M9 16l1 0"/><path d="M14 8l1 0"/><path d="M14 12l1 0"/><path d="M14 16l1 0"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/></svg>
                                @endif
                            </span>
                            <div style="line-height: 1.2;">
                                <div class="text-white" style="font-size: 0.7rem; font-weight: 600;">{{ $isRE ? __('Real Estate Agent') : __('Wholesale') }}</div>
                                <div style="font-size: 0.6rem; color: rgba(255,255,255,0.5);">{{ __('Mode') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
        <!-- Main Content -->
        <main class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                @yield('page-title', __('Dashboard'))
                            </h2>
                        </div>
                        <div class="col-auto ms-auto d-flex align-items-center gap-2">

                            <!-- Global Search -->
                            <div class="position-relative" id="global-search-wrap" style="width: 260px;">
                                <form action="{{ route('search') }}" method="GET" autocomplete="off" role="search">
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
                                        </span>
                                        <input type="search" name="q" id="global-search-input" class="form-control form-control-sm" placeholder="{{ ($businessMode ?? 'wholesale') === 'realestate' ? __('Search leads, transactions, clients...') : __('Search leads, deals, buyers...') }}" value="" aria-label="{{ __('Search') }}">
                                    </div>
                                </form>
                                <div id="search-results-dropdown" class="dropdown-menu w-100 p-0" role="listbox" aria-live="polite" style="display:none; position:absolute; top:100%; left:0; z-index:1050; max-height:360px; overflow-y:auto;"></div>
                            </div>
                            <!-- Recently Viewed -->
                            <div class="dropdown" id="recently-viewed-dropdown">
                                <a href="#" class="btn btn-ghost-secondary btn-icon" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="{{ __('Recently Viewed') }}" title="{{ __('Recently Viewed') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow" style="min-width: 280px;">
                                    <h6 class="dropdown-header">{{ __('Recently Viewed') }}</h6>
                                    <div id="recently-viewed-list">
                                        <div class="dropdown-item text-muted small text-center py-2">{{ __('No recent items') }}</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Notification Bell -->
                            <div class="dropdown" id="notification-bell">
                                <a href="#" class="btn btn-ghost-secondary btn-icon position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="{{ __('Notifications') }}" id="notification-toggle">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2-3v-3a7 7 0 0 1 4-6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                                    <span class="badge bg-red badge-notification badge-pill" id="notif-badge" style="display:none;"></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow p-0" style="width: 360px; max-height: 440px; overflow: hidden;">
                                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                        <h6 class="m-0">{{ __('Notifications') }}</h6>
                                        <a href="#" class="small text-muted" id="notif-mark-all" style="display:none;" onclick="markAllNotificationsRead(event)">{{ __('Mark all read') }}</a>
                                    </div>
                                    <div id="notif-list" style="max-height: 340px; overflow-y: auto;">
                                        <div class="text-center text-muted py-4" id="notif-empty">{{ __('No notifications') }}</div>
                                    </div>
                                    <div class="border-top px-3 py-2 text-center">
                                        <a href="{{ route('notifications.index') }}" class="small">{{ __('View all notifications') }}</a>
                                    </div>
                                </div>
                            </div>
                            <!-- Dark Mode Toggle -->
                            <button class="btn btn-ghost-secondary btn-icon" id="theme-toggle" title="{{ __('Toggle Dark Mode') }}" onclick="toggleTheme()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon theme-icon-light" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon theme-icon-dark" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="4"/><path d="M3 12h1m8-9v1m8 8h1m-9 8v1m-6.4-15.4l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>
                            </button>
                            <!-- User Dropdown -->
                            <div class="dropdown">
                                <a href="#" class="btn btn-outline-secondary" data-bs-toggle="dropdown">
                                    {{ auth()->user()->name }}
                                    <span class="badge bg-secondary-lt ms-1">{{ __(ucwords(str_replace('_', ' ', auth()->user()->role->name))) }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('My Profile') }}</a>
                                    @if(auth()->user()->isAdmin())
                                    <a class="dropdown-item" href="{{ route('settings.index') }}">{{ __('Settings') }}</a>
                                    @endif
                                    <div class="dropdown-divider"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">{{ __('Logout') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @hasSection('breadcrumbs')
                    <div class="row">
                        <div class="col">
                            <nav aria-label="{{ __('Breadcrumb') }}">
                                <ol class="breadcrumb">
                                    @yield('breadcrumbs')
                                </ol>
                            </nav>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="page-body">
                <div class="container-xl" id="main-content">
                    <div id="toast-container" aria-live="polite" aria-atomic="true" style="position:fixed;top:4.5rem;right:1.5rem;z-index:10500;display:flex;flex-direction:column;gap:0.5rem;max-width:400px;"></div>
                    @if(session('success'))
                    <script>document.addEventListener('DOMContentLoaded',function(){window.showToast(@json(session('success')),'success')})</script>
                    @endif
                    @if(session('error'))
                    <script>document.addEventListener('DOMContentLoaded',function(){window.showToast(@json(session('error')),'error')})</script>
                    @endif
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
    @stack('scripts')
    <script>
    // Populate recently viewed dropdown (and register refresh for live updates)
    window._refreshRecentlyViewed = function() {
        var KEY = 'insulacrm_recently_viewed';
        var listEl = document.getElementById('recently-viewed-list');
        if (!listEl) return;
        var items = [];
        try { items = JSON.parse(localStorage.getItem(KEY)) || []; } catch(e) {}
        if (!items.length) return;

        var typeIcons = { lead: 'user', deal: 'briefcase', buyer: 'building', property: 'home' };
        var typeColors = { lead: 'bg-blue', deal: 'bg-purple', buyer: 'bg-green', property: 'bg-orange' };
        listEl.innerHTML = items.map(function(item) {
            var color = typeColors[item.type] || 'bg-secondary';
            return '<a href="' + item.url + '" class="dropdown-item d-flex align-items-center gap-2 py-2">' +
                '<span class="badge ' + color + '-lt" style="min-width:50px;font-size:10px">' + item.type.charAt(0).toUpperCase() + item.type.slice(1) + '</span>' +
                '<span class="text-truncate">' + item.name + '</span></a>';
        }).join('');
    };
    window._refreshRecentlyViewed();
    </script>
    <script>
    (function() {
        const badge = document.getElementById('notif-badge');
        const list = document.getElementById('notif-list');
        const empty = document.getElementById('notif-empty');
        const markAllBtn = document.getElementById('notif-mark-all');
        const toggle = document.getElementById('notif-toggle');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const iconMap = {
            'user-plus': '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M16 11h6M19 8v6"/></svg>',
            'arrow-right': '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><line x1="5" y1="12" x2="19" y2="12"/><line x1="13" y1="18" x2="19" y2="12"/><line x1="13" y1="6" x2="19" y2="12"/></svg>',
            'alert-triangle': '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 001.84-2.75l-7.1-12.25a2 2 0 00-3.5 0l-7.1 12.25a2 2 0 001.75 2.75"/></svg>',
            'users': '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M16 3.13a4 4 0 010 7.75"/><path d="M21 21v-2a4 4 0 00-3-3.85"/></svg>',
            'user-check': '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><path d="M16 11l2 2 4-4"/></svg>',
            'bell': '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z"/><path d="M10 5a2 2 0 014 0 7 7 0 014 6v3a4 4 0 002 3H4a4 4 0 002-3v-3a7 7 0 014-6"/><path d="M9 17v1a3 3 0 006 0v-1"/></svg>'
        };

        const colorMap = {
            blue: 'bg-blue-lt', purple: 'bg-purple-lt', orange: 'bg-orange-lt',
            green: 'bg-green-lt', cyan: 'bg-cyan-lt', red: 'bg-red-lt'
        };

        function loadNotifications() {
            fetch('{{ route('notifications.recent') }}')
                .then(r => r.json())
                .then(data => {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.style.display = '';
                        markAllBtn.style.display = '';
                    } else {
                        badge.style.display = 'none';
                        markAllBtn.style.display = 'none';
                    }

                    if (data.notifications.length === 0) {
                        empty.style.display = '';
                        return;
                    }
                    empty.style.display = 'none';

                    let html = '';
                    data.notifications.forEach(n => {
                        const d = n.data;
                        const icon = iconMap[d.icon] || iconMap.bell;
                        const bg = colorMap[d.color] || 'bg-blue-lt';
                        const unreadClass = n.read ? '' : 'notif-unread';
                        const dot = n.read ? '' : '<span class="badge bg-blue badge-pill ms-1"></span>';
                        const safeUrl = (d.url && /^https?:\/\//.test(d.url)) ? escapeHtml(d.url) : '#';
                        const safeId = escapeHtml(String(n.id));
                        html += `<a href="${safeUrl}" class="d-flex align-items-start gap-2 px-3 py-2 text-decoration-none text-reset border-bottom ${unreadClass}" onclick="markNotificationRead('${safeId}')">
                            <span class="avatar avatar-sm ${bg} flex-shrink-0 mt-1">${icon}</span>
                            <div class="flex-fill" style="min-width:0;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="small">${escapeHtml(d.title || '')}</strong>
                                    ${dot}
                                </div>
                                <div class="text-muted small text-truncate">${escapeHtml(d.body || '')}</div>
                                <div class="text-muted" style="font-size:0.7rem;">${escapeHtml(n.time || '')}</div>
                            </div>
                        </a>`;
                    });
                    list.innerHTML = html + (empty.outerHTML);
                    document.getElementById('notif-empty').style.display = 'none';
                })
                .catch(() => {});
        }

        window.markNotificationRead = function(id) {
            fetch('{{ url("/notifications") }}/' + id + '/read', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrfToken}
            });
        };

        window.markAllNotificationsRead = function(e) {
            e.preventDefault();
            fetch('{{ route('notifications.markAllRead') }}', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrfToken}
            }).then(() => {
                badge.style.display = 'none';
                markAllBtn.style.display = 'none';
                list.querySelectorAll('.notif-unread').forEach(el => el.classList.remove('notif-unread'));
                list.querySelectorAll('.badge-pill').forEach(el => el.remove());
            });
        };

        // Load on page load and refresh every 60 seconds
        loadNotifications();
        setInterval(loadNotifications, 60000);

        // Also refresh when dropdown opens
        if (toggle) {
            toggle.addEventListener('click', loadNotifications);
        }
    })();

    // Dark mode toggle
    (function() {
        const body = document.body;
        const lightIcon = document.querySelector('.theme-icon-light');
        const darkIcon = document.querySelector('.theme-icon-dark');
        if (body.getAttribute('data-bs-theme') === 'dark') {
            if (lightIcon) lightIcon.style.display = 'none';
            if (darkIcon) darkIcon.style.display = '';
        }
        window.toggleTheme = function() {
            const isDark = body.getAttribute('data-bs-theme') === 'dark';
            body.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
            if (lightIcon) lightIcon.style.display = isDark ? '' : 'none';
            if (darkIcon) darkIcon.style.display = isDark ? 'none' : '';
            fetch('{{ route('theme.toggle') }}', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json'}
            });
        };
    })();

    // Global search with live results
    (function() {
        const input = document.getElementById('global-search-input');
        const dropdown = document.getElementById('search-results-dropdown');
        if (!input || !dropdown) return;

        let debounceTimer;
        const typeColors = { lead: 'bg-blue', deal: 'bg-purple', buyer: 'bg-green', property: 'bg-orange' };

        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) { dropdown.style.display = 'none'; return; }

            debounceTimer = setTimeout(function() {
                fetch('{{ route('search') }}?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.results || data.results.length === 0) {
                        dropdown.innerHTML = '<div class="p-3 text-muted small text-center">{{ __("No results found") }}</div>';
                    } else {
                        dropdown.innerHTML = data.results.map(r =>
                            '<a href="' + r.url + '" class="dropdown-item d-flex align-items-center gap-2 py-2">' +
                            '<span class="badge ' + (typeColors[r.type] || 'bg-secondary') + '-lt" style="min-width:65px">' + r.type.charAt(0).toUpperCase() + r.type.slice(1) + '</span>' +
                            '<div><div class="fw-bold small">' + escapeHtml(r.title) + '</div>' +
                            (r.subtitle ? '<div class="text-muted" style="font-size:12px">' + escapeHtml(r.subtitle) + '</div>' : '') +
                            '</div></a>'
                        ).join('');
                    }
                    dropdown.style.display = 'block';
                });
            }, 300);
        });

        input.addEventListener('focus', function() {
            if (dropdown.innerHTML && this.value.trim().length >= 2) dropdown.style.display = 'block';
        });

        document.addEventListener('click', function(e) {
            if (!document.getElementById('global-search-wrap').contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    })();
    </script>
    <script>
    window.showToast = function(message, type) {
        var container = document.getElementById('toast-container');
        if (!container) return;
        var toast = document.createElement('div');
        toast.className = 'alert alert-' + (type === 'error' ? 'danger' : 'success') + ' alert-dismissible shadow-lg';
        toast.setAttribute('role', 'alert');
        toast.style.cssText = 'animation:slideIn .3s ease;margin:0;min-width:280px;';
        toast.innerHTML = message + '<a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>';
        container.appendChild(toast);
        setTimeout(function() {
            toast.style.transition = 'opacity .3s ease';
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    };
    </script>
    <script>
    // Global keyboard shortcuts
    (function() {
        var activeRow = -1;

        document.addEventListener('keydown', function(e) {
            var tag = e.target.tagName;
            var isInput = (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable);

            // ? for help (needs Shift)
            if (e.key === '?' && !isInput) {
                e.preventDefault();
                var modal = document.getElementById('keyboard-shortcuts-modal');
                if (modal) new bootstrap.Modal(modal).show();
                return;
            }

            // Skip if in input or modifier held
            if (isInput || e.ctrlKey || e.altKey || e.metaKey) return;

            if (e.key === '/') {
                e.preventDefault();
                var searchInput = document.getElementById('global-search-input');
                if (searchInput) searchInput.focus();
            } else if (e.key === 'c') {
                window.location.href = '{{ route("leads.create") }}';
            } else if (e.key === 'j' || e.key === 'k') {
                var rows = document.querySelectorAll('.table.card-table tbody tr');
                if (!rows.length) return;
                e.preventDefault();
                if (activeRow >= 0 && activeRow < rows.length) rows[activeRow].classList.remove('table-active');
                if (e.key === 'j') activeRow = Math.min(activeRow + 1, rows.length - 1);
                else activeRow = Math.max(activeRow - 1, 0);
                rows[activeRow].classList.add('table-active');
                rows[activeRow].scrollIntoView({ block: 'nearest' });
            } else if ((e.key === 'Enter' || e.key === 'o') && activeRow >= 0) {
                var rows = document.querySelectorAll('.table.card-table tbody tr');
                if (rows[activeRow]) {
                    var link = rows[activeRow].querySelector('a[href]');
                    if (link) window.location.href = link.href;
                }
            }
        });
    })();
    </script>
    {{-- Quick-Add FAB --}}
    @auth
    <style>
        #quick-add-fab .btn-primary { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        #quick-add-fab .btn-primary:hover { transform: scale(1.1); box-shadow: 0 6px 24px rgba(var(--tblr-primary-rgb), 0.4) !important; }
        #quick-add-fab .dropdown-item { cursor: pointer; transition: background-color 0.15s ease, padding-left 0.15s ease; }
        #quick-add-fab .dropdown-item:hover { background-color: var(--tblr-primary); color: #fff; padding-left: 1.25rem; }
        #quick-add-fab .dropdown-item:hover .icon { stroke: #fff; }
    </style>
    <div class="position-fixed" style="bottom: 24px; right: 24px; z-index: 1050;" id="quick-add-fab">
        <div class="dropup">
            <button class="btn btn-primary btn-icon rounded-circle shadow-lg" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-label="{{ __('Quick Add') }}" style="width: 48px; height: 48px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
            <div class="dropdown-menu dropdown-menu-end mb-1">
                <a class="dropdown-item" href="{{ route('leads.create') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/></svg>
                    {{ __('New Lead') }}
                </a>
                @if(auth()->user()->canManageBuyers())
                <a class="dropdown-item" href="{{ route('buyers.create') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 7v5l3 3"/></svg>
                    {{ __('New') }} {{ $modeTerms['buyer_singular'] ?? __('Buyer') }}
                </a>
                @endif
            </div>
        </div>
    </div>
    @endauth
    <div class="modal modal-blur fade" id="keyboard-shortcuts-modal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Keyboard Shortcuts') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr><td><kbd>/</kbd></td><td>{{ __('Focus search') }}</td></tr>
                            <tr><td><kbd>c</kbd></td><td>{{ __('Create new lead') }}</td></tr>
                            <tr><td><kbd>j</kbd> / <kbd>k</kbd></td><td>{{ __('Navigate table rows') }}</td></tr>
                            <tr><td><kbd>o</kbd></td><td>{{ __('Open selected row') }}</td></tr>
                            <tr><td><kbd>?</kbd></td><td>{{ __('Show this help') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <style>
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
    }
    .notif-unread { background: var(--tblr-bg-surface-secondary, #f0f6ff); border-left: 3px solid var(--tblr-primary, #206bc4); }
    </style>
    @include('layouts._broadcasting')
</body>
</html>
