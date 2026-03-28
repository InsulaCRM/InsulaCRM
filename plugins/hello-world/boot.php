<?php

use App\Facades\Hooks;

// Example: Log when a lead is created
Hooks::addAction('lead.created', function ($lead) {
    \Illuminate\Support\Facades\Log::info("[HelloWorld Plugin] New lead created: {$lead->first_name} {$lead->last_name}");
});

// Example: Log when a deal stage changes
Hooks::addAction('deal.stage_changed', function ($deal, $oldStage) {
    \Illuminate\Support\Facades\Log::info("[HelloWorld Plugin] Deal #{$deal->id} moved from {$oldStage} to {$deal->stage}");
});
