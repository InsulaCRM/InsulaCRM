<?php

namespace App\Providers;

use App\Helpers\TenantFormatHelper;
use App\Models\Buyer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use App\Models\OpenHouse;
use App\Models\Showing;
use App\Models\User;
use App\Policies\BuyerPolicy;
use App\Policies\DealPolicy;
use App\Policies\LeadPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\OpenHousePolicy;
use App\Policies\ShowingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        AliasLoader::getInstance()->alias('Fmt', TenantFormatHelper::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tabler');
        Paginator::defaultSimpleView('vendor.pagination.tabler');

        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Deal::class, DealPolicy::class);
        Gate::policy(Buyer::class, BuyerPolicy::class);
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Showing::class, ShowingPolicy::class);
        Gate::policy(OpenHouse::class, OpenHousePolicy::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->header('X-API-Key') ?: $request->ip());
        });

        Event::listen(
            \App\Events\DealStageChanged::class,
            \App\Listeners\SummarizeOnStageChange::class
        );

        // Wire workflow triggers into the hook system
        $this->registerWorkflowHooks();
    }

    /**
     * Register WorkflowEngine triggers for existing hook actions.
     */
    protected function registerWorkflowHooks(): void
    {
        /** @var \App\Services\HookManager $hooks */
        $hooks = app(\App\Services\HookManager::class);

        $hooks->addAction('lead.created', function ($lead) {
            try {
                app(\App\Services\WorkflowEngine::class)->trigger('lead_created', $lead);
            } catch (\Throwable $e) {
                Log::error("Workflow trigger lead_created failed: {$e->getMessage()}");
            }
        }, 99);

        $hooks->addAction('lead.status_changed', function ($lead, $oldStatus = null) {
            try {
                app(\App\Services\WorkflowEngine::class)->trigger('lead_status_changed', $lead, [
                    'old_status' => $oldStatus,
                    'new_status' => $lead->status,
                ]);
            } catch (\Throwable $e) {
                Log::error("Workflow trigger lead_status_changed failed: {$e->getMessage()}");
            }
        }, 99);

        $hooks->addAction('deal.stage_changed', function ($deal, $oldStage = null) {
            try {
                app(\App\Services\WorkflowEngine::class)->trigger('deal_stage_changed', $deal, [
                    'old_stage' => $oldStage,
                    'new_stage' => $deal->stage,
                ]);
            } catch (\Throwable $e) {
                Log::error("Workflow trigger deal_stage_changed failed: {$e->getMessage()}");
            }
        }, 99);

        $hooks->addAction('activity.logged', function ($activity) {
            try {
                app(\App\Services\WorkflowEngine::class)->trigger('activity_logged', $activity);
            } catch (\Throwable $e) {
                Log::error("Workflow trigger activity_logged failed: {$e->getMessage()}");
            }
        }, 99);

        $hooks->addAction('buyer.notified', function ($buyer, $deal = null) {
            try {
                app(\App\Services\WorkflowEngine::class)->trigger('new_buyer_match', $buyer, [
                    'deal_id' => $deal?->id,
                ]);
            } catch (\Throwable $e) {
                Log::error("Workflow trigger new_buyer_match failed: {$e->getMessage()}");
            }
        }, 99);
    }
}
