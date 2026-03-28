@props(['totalLeads', 'leadsThisMonth', 'activeDeals', 'totalPipelineValue', 'closedThisMonth', 'feesThisMonth', 'hotLeads', 'overdueTasks'])

<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('leads.index') }}" class="card card-sm text-decoration-none" style="color: inherit;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-primary text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $leadsThisMonth }} {{ __('Leads This Month') }}</div>
                        <div class="text-secondary">{{ $totalLeads }} {{ __('total') }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('pipeline') }}" class="card card-sm text-decoration-none" style="color: inherit;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-green text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 7v5l3 3"/></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $activeDeals }} {{ __('Active') }} {{ $modeTerms['deal_label'] ?? __('Deals') }}</div>
                        <div class="text-secondary">{{ Fmt::currency($totalPipelineValue, 0) }} {{ strtolower($modeTerms['pipeline_label'] ?? __('pipeline')) }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('pipeline', ['stage' => 'closing']) }}" class="card card-sm text-decoration-none" style="color: inherit;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-yellow text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/><path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5m2 0v1.5m0 -9v1.5"/></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $closedThisMonth }} {{ __('Closed This Month') }}</div>
                        <div class="text-secondary">{{ Fmt::currency($feesThisMonth, 0) }} {{ $modeTerms['fee_label'] ?? __('in fees') }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('leads.index', ['temperature' => 'hot']) }}" class="card card-sm text-decoration-none" style="color: inherit;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-{{ $overdueTasks > 0 ? 'danger' : 'azure' }} text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3.5 5.5l1.5 1.5l2.5 -2.5"/><path d="M3.5 11.5l1.5 1.5l2.5 -2.5"/><path d="M3.5 17.5l1.5 1.5l2.5 -2.5"/><path d="M11 6h9"/><path d="M11 12h9"/><path d="M11 18h9"/></svg>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">{{ $hotLeads }} {{ __('Hot Leads') }}</div>
                        <div class="text-secondary">{{ $overdueTasks }} {{ __('overdue task') }}{{ $overdueTasks !== 1 ? 's' : '' }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
