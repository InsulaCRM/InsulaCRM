# Plugin Developer Guide

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Directory Structure](#directory-structure)
3. [Plugin Manifest (plugin.json)](#plugin-manifest)
4. [The boot.php Entry Point](#the-bootphp-entry-point)
5. [BasePlugin Class](#baseplugin-class)
6. [Hooks and Filters](#hooks-and-filters)
7. [Available Action Hooks](#available-action-hooks)
8. [Using Filters](#using-filters)
9. [Menu Items, Dashboard Widgets, and Settings Tabs](#menu-items-dashboard-widgets-and-settings-tabs)
10. [Routes and Migrations](#routes-and-migrations)
11. [Using the REST API from Plugins](#using-the-rest-api-from-plugins)
12. [HelloWorld Plugin Walkthrough](#helloworld-plugin-walkthrough)
13. [Advanced Plugin Example](#advanced-plugin-example)
14. [Packaging and Distribution](#packaging-and-distribution)
15. [Security Considerations](#security-considerations)
16. [Troubleshooting](#troubleshooting)

---

## Architecture Overview

The CRM uses a lightweight, WordPress-inspired plugin system built on three core components:

| Component | Location | Responsibility |
|---|---|---|
| **HookManager** | `app/Services/HookManager.php` | Registers and dispatches actions and filters with priority support |
| **PluginManager** | `app/Services/PluginManager.php` | Discovers, boots, and tracks active plugins from the database |
| **Hooks Facade** | `app/Facades/Hooks.php` | Provides a static interface (`Hooks::addAction(...)`) to the HookManager singleton |
| **BasePlugin** | `app/Plugins/BasePlugin.php` | Abstract class offering convenience methods for menu items, widgets, settings tabs, routes, and migrations |

**Lifecycle:**

1. On application boot, `PluginManager::bootAll()` queries the `plugins` table for rows where `is_active = true`.
2. For each active plugin, the manager locates `plugins/{slug}/boot.php` and executes it inside a `try/catch`.
3. Inside `boot.php`, the plugin registers callbacks on named hooks via the `Hooks` facade.
4. When CRM controllers perform actions (creating leads, changing deal stages, etc.), they call `Hooks::doAction(...)` or `Hooks::applyFilter(...)`, which dispatches all registered callbacks in priority order.

---

## Directory Structure

Every plugin lives under the `plugins/` directory at the project root:

```
plugins/
  my-plugin/
    plugin.json        # Required - manifest file
    boot.php           # Required - entry point
    src/               # Optional - PHP classes
      MyPluginService.php
    routes/            # Optional - web routes
      web.php
    database/          # Optional - migrations
      migrations/
        2025_01_01_000000_create_custom_table.php
    resources/         # Optional - views
      views/
        widget.blade.php
        settings.blade.php
```

The only two files that are **required** are `plugin.json` and `boot.php`. Everything else is optional and depends on the complexity of your plugin.

---

## Plugin Manifest

The `plugin.json` file describes your plugin to the system. It is read when the plugin is installed and its values are stored in the `plugins` database table.

```json
{
    "name": "My Custom Plugin",
    "slug": "my-custom-plugin",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "A short description of what this plugin does."
}
```

### Fields

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Human-readable display name |
| `slug` | string | Yes | Unique identifier, used as the directory name. Must be lowercase and use hyphens (e.g., `my-plugin`). |
| `version` | string | Yes | Semantic version (e.g., `1.0.0`) |
| `author` | string | Yes | Author or organization name |
| `description` | string | Yes | Brief description shown in the plugin list |

The `slug` must exactly match the plugin's directory name under `plugins/`.

### Database Record

When a plugin is installed, a corresponding row is created in the `plugins` table:

| Column | Type | Description |
|---|---|---|
| `tenant_id` | integer | The tenant this plugin installation belongs to |
| `name` | string | From `plugin.json` |
| `slug` | string | From `plugin.json` |
| `version` | string | From `plugin.json` |
| `author` | string | From `plugin.json` |
| `description` | string | From `plugin.json` |
| `is_active` | boolean | Whether the plugin is currently enabled |
| `installed_at` | datetime | When the plugin was installed |

---

## The boot.php Entry Point

`boot.php` is the first file executed when your plugin loads. It is included via `require_once` by the `PluginManager`. This file should register all of your hook callbacks.

### Minimal Example

```php
<?php

use App\Facades\Hooks;

Hooks::addAction('lead.created', function ($lead) {
    // Your logic here
});
```

### Important Notes

- `boot.php` is executed in the global scope. You have access to all Laravel facades and services.
- The file is loaded via `require_once`, so it runs exactly once per request.
- If `boot.php` throws an uncaught exception, the `PluginManager` catches it and logs the error. The rest of the application continues to function. However, you should still wrap risky logic in your own try/catch blocks (see [Security Considerations](#security-considerations)).
- Avoid heavy computation in `boot.php` itself. Register callbacks and defer work to when hooks actually fire.

---

## BasePlugin Class

For more complex plugins, you can extend `App\Plugins\BasePlugin` instead of writing everything in `boot.php`. BasePlugin provides structured lifecycle methods and convenience helpers.

### Class Signature

```php
abstract class BasePlugin
{
    protected HookManager $hooks;
    protected string $basePath;
    protected array $manifest;

    public function __construct(HookManager $hooks, string $basePath, array $manifest);

    abstract public function register(): void;  // Register services, bindings, hooks
    abstract public function boot(): void;       // Boot logic after all plugins are registered
}
```

### Using BasePlugin from boot.php

```php
<?php
// plugins/my-plugin/boot.php

use App\Plugins\BasePlugin;
use App\Services\HookManager;

class MyPlugin extends BasePlugin
{
    public function register(): void
    {
        $this->hooks->addAction('lead.created', [$this, 'onLeadCreated']);
    }

    public function boot(): void
    {
        $this->addMenuItem('My Plugin', 'plugin.my-plugin.index', 'fas fa-cog');
        $this->addDashboardWidget('my-plugin::widget', 50);
        $this->addSettingsTab('My Plugin', 'my-plugin::settings');
        $this->loadRoutes(__DIR__ . '/routes/web.php');
        $this->registerMigrations(__DIR__ . '/database/migrations');
    }

    public function onLeadCreated($lead): void
    {
        \Log::info("MyPlugin: Lead {$lead->id} created");
    }
}

// Instantiate and run
$hooks = app(HookManager::class);
$manifest = json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
$plugin = new MyPlugin($hooks, __DIR__, $manifest);
$plugin->register();
$plugin->boot();
```

### Available BasePlugin Methods

| Method | Description |
|---|---|
| `addMenuItem(string $label, string $route, string $icon)` | Adds a link to the CRM sidebar navigation |
| `addDashboardWidget(string $view, int $position)` | Registers a Blade view as a dashboard widget. Lower `$position` values appear first. |
| `addSettingsTab(string $label, string $view)` | Adds a tab to the Settings page with the given Blade view |
| `loadRoutes(string $routesFile)` | Loads a routes file under the `web`, `auth`, and `tenant` middleware with prefix `plugin/{slug}` |
| `registerMigrations(string $path)` | Registers a directory of migration files to run with `php artisan migrate` |
| `getSlug(): string` | Returns the plugin slug |
| `getManifest(): array` | Returns the full parsed `plugin.json` |
| `getBasePath(): string` | Returns the absolute filesystem path to the plugin directory |

---

## Hooks and Filters

The hook system distinguishes between **actions** and **filters**:

- **Actions** perform side effects (logging, sending notifications, creating records). They do not return a value.
- **Filters** transform data. The callback receives a value, modifies it, and returns the modified value.

### Registering an Action

```php
use App\Facades\Hooks;

Hooks::addAction('hook.name', callable $callback, int $priority = 10);
```

- `$callback` receives whatever arguments the `doAction()` call passes.
- `$priority` controls execution order. Lower numbers run first. Default is `10`.

### Registering a Filter

```php
use App\Facades\Hooks;

Hooks::addFilter('filter.name', callable $callback, int $priority = 10);
```

- `$callback` receives the current value as its first argument, plus any additional arguments.
- **You must return the (modified or unmodified) value.** Failing to return will set the value to `null`.

### Priority

Multiple callbacks on the same hook are sorted by priority (ascending) before execution. Within the same priority level, callbacks execute in the order they were registered.

```php
// Runs second (priority 20)
Hooks::addAction('lead.created', function ($lead) {
    // ...
}, 20);

// Runs first (priority 5)
Hooks::addAction('lead.created', function ($lead) {
    // ...
}, 5);
```

---

## Available Action Hooks

The following hooks are currently fired by the CRM core. Each section lists the hook name, where it fires, and the arguments your callback receives.

### lead.created

Fired after a new lead is created and saved to the database.

```php
Hooks::addAction('lead.created', function ($lead) {
    // $lead - App\Models\Lead instance
});
```

**Fired in:** `LeadController::store()`

### lead.updated

Fired after an existing lead is updated.

```php
Hooks::addAction('lead.updated', function ($lead) {
    // $lead - App\Models\Lead instance (with updated attributes)
});
```

**Fired in:** `LeadController::update()`

### lead.status_changed

Fired when a lead's status field changes (via web UI or API).

```php
Hooks::addAction('lead.status_changed', function ($lead, $oldStatus) {
    // $lead      - App\Models\Lead instance (with new status)
    // $oldStatus - string, the previous status value
});
```

**Fired in:** `LeadController::update()`, `LeadController::updateStatus()`, `LeadController::bulkAction()`

### deal.stage_changed

Fired after a deal's stage is updated.

```php
Hooks::addAction('deal.stage_changed', function ($deal, $oldStage) {
    // $deal     - App\Models\Deal instance (with new stage)
    // $oldStage - string, the previous stage value
});
```

**Fired in:** `DealController::updateStage()`

### activity.logged

Fired after an activity (call, email, note, etc.) is recorded against a lead.

```php
Hooks::addAction('activity.logged', function ($activity) {
    // $activity - App\Models\Activity instance
});
```

**Fired in:** `ActivityController::store()`

### buyer.notified

Fired after a buyer is notified about a matching deal.

```php
Hooks::addAction('buyer.notified', function ($buyer, $deal) {
    // $buyer - App\Models\Buyer instance
    // $deal  - App\Models\Deal instance
});
```

**Fired in:** `DealController::notifyBuyer()`

### sequence.step_executed

Fired after a drip sequence step is processed (email, SMS, call, etc.).

```php
Hooks::addAction('sequence.step_executed', function ($lead, $stepData) {
    // $lead     - App\Models\Lead instance
    // $stepData - array with step details (action_type, message_template, delay_days, etc.)
});
```

**Fired in:** `ProcessSequences` command (daily cron)

---

## Outbound Webhooks

The CRM includes a built-in outbound webhook system that fires HTTP POST requests to external URLs when CRM events occur. This is separate from the internal hook system — webhooks are HTTP-based and configured by tenants via Settings > Webhooks.

### How Webhooks Work

1. Tenants configure webhook endpoints at Settings > Webhooks with a URL, optional HMAC secret, and event subscriptions.
2. When a subscribed event fires, the `WebhookService` dispatches a queued `DispatchWebhook` job.
3. The job sends an HTTP POST with a JSON payload signed with HMAC-SHA256 (if a secret is set).
4. Failed deliveries are retried 3 times with exponential backoff (10s, 60s, 300s).
5. After 10 consecutive failures, the webhook is automatically disabled.

### Webhook Payload Format

```json
{
    "event": "lead.created",
    "timestamp": "2026-03-10T15:00:00+00:00",
    "data": {
        "lead_id": 42,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "555-0100",
        "source": "website",
        "status": "new"
    }
}
```

### Webhook Headers

| Header | Description |
|--------|-------------|
| `Content-Type` | `application/json` |
| `X-Webhook-Event` | Event name (e.g., `lead.created`) |
| `X-Webhook-Signature` | HMAC-SHA256 hex digest of the JSON body (only if secret is configured) |
| `User-Agent` | `InsulaCRM-Webhook/1.0` |

### Verifying Webhook Signatures

If you configured a secret, verify the signature on the receiving end:

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$expected = hash_hmac('sha256', $payload, $yourSecret);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}
```

### Dispatching Webhooks from Plugins

Plugins can trigger webhook deliveries for custom events:

```php
use App\Services\WebhookService;

Hooks::addAction('my-plugin.custom-event', function ($data) {
    WebhookService::dispatch('my-plugin.custom-event', [
        'key' => 'value',
    ], auth()->user()->tenant_id);
});
```

Note: Tenants must subscribe to your custom event name in their webhook configuration. Consider documenting your plugin's custom events for users.

### Events That Trigger Webhooks

| Event | Data Payload |
|-------|-------------|
| `lead.created` | lead_id, first_name, last_name, email, phone, source, status |
| `lead.updated` | lead_id, first_name, last_name, status, temperature |
| `deal.stage_changed` | deal_id, title, old_stage, new_stage, agent_id |

---

## Using Filters

Filters allow plugins to modify data before it is used by the core application. While the CRM core does not yet fire many filters, the infrastructure is fully in place for both core and plugin-to-plugin use.

### Example: Modifying Data Before Display

```php
// Plugin A registers a filter
Hooks::addFilter('lead.display_name', function ($name, $lead) {
    return $name . ' [VIP]';
}, 10);
```

### Firing a Filter from Your Own Plugin

You can create your own filter hooks to let other plugins extend your functionality:

```php
// In your plugin - fire the filter
$widgets = Hooks::applyFilter('my-plugin.widgets', $defaultWidgets);

// In another plugin - modify the value
Hooks::addFilter('my-plugin.widgets', function ($widgets) {
    $widgets[] = 'extra-widget';
    return $widgets;
});
```

### Filter vs Action: When to Use Which

| Use Case | Type |
|---|---|
| Send a notification when something happens | Action |
| Log an event | Action |
| Modify a value before it is saved or displayed | Filter |
| Add items to a list before rendering | Filter |

---

## Menu Items, Dashboard Widgets, and Settings Tabs

These features are available through the `BasePlugin` class.

### Adding a Sidebar Menu Item

```php
public function boot(): void
{
    $this->addMenuItem('Reports', 'plugin.my-plugin.reports', 'fas fa-chart-bar');
}
```

- **$label**: Text shown in the sidebar.
- **$route**: Named Laravel route. Routes loaded via `loadRoutes()` are prefixed with `plugin/{slug}`.
- **$icon**: Font Awesome icon class. Defaults to `fas fa-puzzle-piece`.

### Adding a Dashboard Widget

```php
public function boot(): void
{
    $this->addDashboardWidget('my-plugin::dashboard-widget', 50);
}
```

- **$view**: A Blade view name. Use `{slug}::viewname` if you register a view namespace.
- **$position**: Integer controlling display order. Lower values appear first. Default is `100`.

### Adding a Settings Tab

```php
public function boot(): void
{
    $this->addSettingsTab('API Configuration', 'my-plugin::settings-api');
}
```

- **$label**: Tab title shown on the settings page.
- **$view**: Blade view to render as the tab content.

---

## Routes and Migrations

### Routes

Use `BasePlugin::loadRoutes()` to register routes. They are automatically wrapped in `web`, `auth`, and `tenant` middleware, and prefixed with `plugin/{slug}`.

```php
// plugins/my-plugin/routes/web.php

use Illuminate\Support\Facades\Route;
use Plugins\MyPlugin\Controllers\ReportController;

Route::get('/reports', [ReportController::class, 'index'])->name('plugin.my-plugin.reports');
Route::get('/reports/{id}', [ReportController::class, 'show'])->name('plugin.my-plugin.reports.show');
```

These routes become accessible at `/plugin/my-plugin/reports` and `/plugin/my-plugin/reports/123`.

### Migrations

Use `BasePlugin::registerMigrations()` to include migration files. They will be picked up automatically when `php artisan migrate` runs.

```php
public function boot(): void
{
    $this->registerMigrations(__DIR__ . '/database/migrations');
}
```

Migration files follow the standard Laravel naming convention:

```
plugins/my-plugin/database/migrations/2025_06_01_000000_create_reports_table.php
```

---

## Using the REST API from Plugins

The CRM includes a full REST API at `/api/v1/` with 20 endpoints covering leads, deals, buyers, properties, activities, and stats. Plugins can leverage this API in two ways:

1. **Internal use** — call API controllers directly from plugin code (no HTTP overhead)
2. **External integrations** — make HTTP requests to the API from external services your plugin connects to

### Available API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/leads` | List leads (filterable by status, source, since) |
| POST | `/api/v1/leads` | Create a lead with optional property |
| GET | `/api/v1/leads/{id}` | Get a single lead |
| PUT | `/api/v1/leads/{id}` | Update a lead |
| GET | `/api/v1/deals` | List deals (filterable by stage, agent, since) |
| POST | `/api/v1/deals` | Create a deal |
| GET | `/api/v1/deals/stages` | Get all deal stage definitions |
| GET | `/api/v1/deals/{id}` | Get a single deal |
| PUT | `/api/v1/deals/{id}` | Update a deal (stage changes trigger hooks) |
| GET | `/api/v1/buyers` | List buyers (filterable by search, state) |
| POST | `/api/v1/buyers` | Create a buyer |
| GET | `/api/v1/buyers/{id}` | Get a single buyer |
| PUT | `/api/v1/buyers/{id}` | Update a buyer |
| GET | `/api/v1/properties` | List properties (filterable by type, state, zip) |
| POST | `/api/v1/properties` | Create a property |
| GET | `/api/v1/properties/{id}` | Get a single property |
| PUT | `/api/v1/properties/{id}` | Update a property |
| GET | `/api/v1/activities` | List activities (filterable by lead, type, agent) |
| POST | `/api/v1/activities` | Log an activity |
| GET | `/api/v1/stats` | Get KPIs, pipeline breakdown, trends |

### Authentication

All API requests require a tenant API key passed via the `X-API-Key` header. Query-string API keys are intentionally rejected to avoid leaking credentials into logs, browser history, and referrer headers. Tenants generate their key at Settings > API.

### Calling the API Internally from a Plugin

When your plugin needs to create or read CRM data, you can call the API controllers directly without making HTTP requests. This avoids API key authentication and is more efficient:

```php
use App\Http\Controllers\Api\LeadIngestController;
use Illuminate\Http\Request;

Hooks::addAction('some.event', function ($data) {
    // Create a synthetic request with the data
    $request = Request::create('/api/v1/leads', 'POST', [
        'first_name' => $data['name'],
        'last_name'  => $data['surname'],
        'phone'      => $data['phone'],
        'source'     => 'my_plugin',
    ]);

    // Set the tenant on the request (required by API controllers)
    $request->attributes->set('tenant', auth()->user()->tenant);

    $controller = app(LeadIngestController::class);
    $response = $controller->store($request);
});
```

### Building External Integrations

A common plugin pattern is syncing CRM data with external services. Use the API endpoints from your external service, or dispatch queued jobs from hooks:

```php
use Illuminate\Support\Facades\Http;

Hooks::addAction('deal.stage_changed', function ($deal, $oldStage) {
    if ($deal->stage === 'closed_won') {
        // Notify an external accounting system
        \App\Jobs\SyncClosedDealJob::dispatch($deal);
    }
});

// In your job class, call the external service
// and optionally update the CRM via the API
```

### Extending the API

Plugins can add their own API routes alongside the core endpoints. Use `loadRoutes()` for web routes, or register API routes directly:

```php
// plugins/my-plugin/routes/api.php
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/plugin/my-plugin')->middleware('api.key')->group(function () {
    Route::get('/custom-data', [MyPluginApiController::class, 'index']);
    Route::post('/webhook', [MyPluginApiController::class, 'webhook']);
});
```

Load the API routes in your `boot.php`:

```php
public function boot(): void
{
    // Load web routes (auto-prefixed with plugin/{slug})
    $this->loadRoutes(__DIR__ . '/routes/web.php');

    // Load API routes (manually registered for API prefix)
    if (file_exists(__DIR__ . '/routes/api.php')) {
        require __DIR__ . '/routes/api.php';
    }
}
```

### Using the AI Service from Plugins

If the tenant has AI enabled, plugins can use the `AiService` to generate content, analyze data, or build AI-powered features:

```php
use App\Services\AiService;

Hooks::addAction('deal.stage_changed', function ($deal, $oldStage) {
    try {
        $tenant = $deal->tenant ?? \App\Models\Tenant::find($deal->tenant_id);
        $ai = new AiService($tenant);

        if ($ai->isAvailable() && $deal->stage === 'dispositions') {
            // Auto-generate a deal summary for the buyer sheet
            $analysis = $ai->analyzeDeal($deal);
            // Store or email the analysis...
        }
    } catch (\Throwable $e) {
        \Log::error("[MyPlugin] AI analysis failed: " . $e->getMessage());
    }
});
```

The `AiService` exposes these methods:

**Lead & Activity:**
- `isAvailable(): bool` — Check if AI is configured and enabled
- `draftFollowUp(Lead $lead, string $type): string` — Generate follow-up content. Supported types: `sms`, `email`, `voicemail`, `call`, `direct_mail`, `note`, `meeting`. Automatically injects the logged-in user's name and tenant company name.
- `summarizeNotes(Lead $lead): string` — Summarize activity history with motivation level and next steps
- `scoreLeadMotivation(Lead $lead): array` — Returns `['score' => int, 'confidence' => string, 'factors' => array, 'recommendation' => string]`. Score is saved to `ai_motivation_score` (separate from the automated `motivation_score`).
- `qualifyLead(Lead $lead, ?string $listType = null): array` — Returns `['temperature' => 'hot|warm|cold', 'reasoning' => string]`
- `flagDncRisks(Lead $lead): array` — Returns `['risk_level' => string, 'flags' => array, 'recommendation' => string]`
- `generateObjectionResponses(Lead $lead, ?string $specificObjection = null): string` — Tailored objection handling scripts
- `suggestTasks(Lead $lead): array` — Returns array of `['title' => string, 'days_from_now' => int, 'priority' => 'high|medium|low', 'reason' => string]`

**Property:**
- `suggestOfferStrategy(Property $property): string` — Offer strategy with negotiation tactics
- `generatePropertyDescription(Property $property): string` — Marketing-ready property description

**Deals & Pipeline:**
- `analyzeDeal(Deal $deal): string` — Risk/opportunity assessment with scoring and recommendations
- `adviseDealStage(Deal $deal): string` — Stage-specific advice and next actions
- `draftBuyerMessage(Deal $deal, Buyer $buyer): string` — Buyer outreach email with caller identity
- `explainBuyerMatch(Deal $deal, Buyer $buyer, ?DealBuyerMatch $match = null): string` — Explain why buyer matches deal

**Sequences:**
- `draftSequenceStep(string $sequenceName, int $stepNumber, int $totalSteps, string $actionType, int $delayDays, ?string $previousStepSummary): string` — Generate drip sequence message template with merge tags
- `generateAllSequenceSteps(string $sequenceName, array $steps): array` — Bulk generate templates for all steps, returns array of strings

**Reporting & Import:**
- `generateWeeklyDigest(array $kpiData): string` — Natural language summary of weekly business KPIs
- `suggestCsvMapping(array $headers, array $sampleRows): array` — Returns `[columnIndex => 'crm_field_name', ...]`

**Formatting Helper:**
- `\Fmt::currency($amount)` — Locale-aware currency formatting based on tenant settings
- `\Fmt::area($sqft)` — Converts to tenant's measurement system (sq ft or m²)
- `\Fmt::date($datetime)` — Formats date per tenant preference

The AI provider (OpenAI, Anthropic, Gemini, Ollama, or Custom OpenAI-compatible) is configured per-tenant at Settings > AI. All AI outputs are clean (no preamble or commentary) and ready to use directly in the CRM.

### Translations / i18n

The CRM uses Laravel's `__()` helper with JSON language files for multi-language support. 774 core strings are translated into 7 languages (English, Dutch, German, French, Spanish, Portuguese, Italian). System enum labels (lead statuses, deal stages, property types, etc.) are translated at the source via `CustomFieldService` and `Deal::stageLabels()`.

If your plugin adds Blade views with user-visible text, wrap strings with `__()` for translation support:

```php
// In your plugin Blade views:
<h3>{{ __('My Plugin Dashboard') }}</h3>
<button>{{ __('Generate Report') }}</button>
```

Users can add translations for your plugin strings by including them in their `lang/xx.json` files or via the built-in **Language Editor** at Settings > Languages. Any string not found in the translation file falls back to the English key.

If your plugin displays system values (like lead statuses or deal stages), use the translated helpers rather than raw slug formatting:

```php
// WRONG - displays raw slug without translation
{{ ucfirst(str_replace('_', ' ', $deal->stage)) }}

// CORRECT - uses translated label
{{ \App\Models\Deal::stageLabel($deal->stage) }}

// CustomFieldService labels are already translated when retrieved
@foreach(\App\Services\CustomFieldService::getOptions('lead_status') as $val => $label)
    <option value="{{ $val }}">{{ $label }}</option>  {{-- $label is already translated --}}
@endforeach
```

### Tags System

The CRM includes a polymorphic tagging system that plugins can leverage. Tags can be attached to leads and deals via the `Tag` model with `morphToMany` relationships.

```php
use App\Models\Tag;
use App\Models\Lead;

// Get all tags for a lead
$lead = Lead::find($id);
$tags = $lead->tags; // Collection of Tag models

// Attach a tag
$tag = Tag::where('name', 'VIP')->first();
$lead->tags()->syncWithoutDetaching([$tag->id]);

// Detach a tag
$lead->tags()->detach($tag->id);

// Create a tag (tenant-scoped, reuses existing by name)
$tag = Tag::firstOrCreate(
    ['tenant_id' => $tenantId, 'name' => 'Priority'],
    ['color' => 'red']
);
```

Available tag colors: `blue`, `green`, `red`, `yellow`, `purple`, `orange`, `cyan`, `pink`, `dark`.

Tags are also visible on Lead Kanban cards and can be used for filtering and segmentation in plugin views.

### Calendar Events

Plugins can hook into calendar events by creating tasks or activities with the appropriate dates. The calendar displays:
- **Tasks** with `due_date` — shown as blue events
- **Activities** of type `meeting` or `call` — shown as green/purple events

```php
use App\Models\Task;

// Create a task that appears on the calendar
Task::create([
    'tenant_id' => $tenantId,
    'lead_id' => $leadId,
    'agent_id' => $agentId,
    'title' => 'Plugin-created follow-up',
    'due_date' => now()->addDays(3)->format('Y-m-d'),
]);
```

### Registering Custom 2FA Providers

Plugins can register custom two-factor authentication providers via the Integration Framework. Implement the `TwoFactorProviderInterface` and register it with the `IntegrationManager`:

```php
use App\Contracts\Integrations\TwoFactorProviderInterface;
use App\Integrations\IntegrationManager;
use App\Models\User;

class Duo2faProvider implements TwoFactorProviderInterface
{
    public function driver(): string { return 'duo'; }
    public function name(): string { return 'Duo Security'; }

    public function beginSetup(User $user): array
    {
        // Return data needed for your setup view
        return ['enrollment_url' => '...'];
    }

    public function confirmSetup(User $user, array $input): bool
    {
        // Verify enrollment, save credentials, return true on success
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_provider' => 'duo',
            'two_factor_secret' => Crypt::encryptString($duoUserId),
            'two_factor_recovery_codes' => encrypt($this->generateRecoveryCodes()),
        ]);
        return true;
    }

    public function generateRecoveryCodes(): array
    {
        return array_map(fn() => strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4))), range(1, 8));
    }

    public function verify(User $user, string $code): bool
    {
        // Verify code against Duo API
        return $this->duoApi->verify($user, $code);
    }

    public function challengeView(): string { return 'plugin-duo-2fa::challenge'; }
    public function setupView(): string { return 'plugin-duo-2fa::setup'; }
    public function requiresConfig(): bool { return true; }

    public function configFields(): array
    {
        return [
            ['key' => 'integration_key', 'label' => 'Integration Key', 'type' => 'text'],
            ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password'],
            ['key' => 'api_hostname', 'label' => 'API Hostname', 'type' => 'url'],
        ];
    }
}
```

Register in your plugin's `register()` method:

```php
public function register(): void
{
    app(IntegrationManager::class)->registerDriver('2fa', 'duo', Duo2faProvider::class);
}
```

The provider will automatically appear in the admin's Settings > Integrations tab. The admin enables it, configures any required API keys, and it becomes available to users during 2FA setup.

### Registering Custom SSO Providers

Plugins can add SSO (Single Sign-On) providers by implementing `SsoProviderInterface`:

```php
use App\Contracts\Integrations\SsoProviderInterface;
use App\Contracts\Integrations\SsoResult;
use App\Models\Tenant;
use Illuminate\Http\Request;

class GoogleOAuthProvider implements SsoProviderInterface
{
    public function driver(): string { return 'google-oauth'; }
    public function name(): string { return 'Google Workspace'; }

    public function redirectUrl(Tenant $tenant, array $config): string
    {
        // Build the Google OAuth redirect URL using $config['client_id']
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => route('sso.callback', ['driver' => 'google-oauth']),
            'response_type' => 'code',
            'scope' => 'email profile',
            'state' => csrf_token(),
        ]);
    }

    public function handleCallback(Tenant $tenant, array $config, Request $request): SsoResult
    {
        // Exchange code for token, fetch user info
        $userInfo = $this->fetchGoogleUser($request->code, $config);

        return new SsoResult(
            email: $userInfo['email'],
            name: $userInfo['name'],
            attributes: ['google_id' => $userInfo['sub']],
        );
    }

    public function requiresConfig(): bool { return true; }

    public function configFields(): array
    {
        return [
            ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text'],
            ['key' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password'],
        ];
    }
}
```

Register in your plugin:

```php
public function register(): void
{
    app(IntegrationManager::class)->registerDriver('sso', 'google-oauth', GoogleOAuthProvider::class);
}
```

SSO login buttons will automatically appear on the login page for tenants that have enabled the provider. The SSO flow handles user matching by email within the tenant, and respects 2FA if enabled.

### Registering Custom SMS Providers

Plugins can add SMS providers by implementing `SmsProviderInterface`:

```php
use App\Contracts\Integrations\SmsProviderInterface;

class TwilioSmsProvider implements SmsProviderInterface
{
    public function driver(): string { return 'twilio'; }
    public function name(): string { return 'Twilio SMS'; }

    public function send(string $to, string $message): bool
    {
        // Get tenant config
        $tenant = auth()->user()->tenant;
        $integration = \App\Models\Integration::where('tenant_id', $tenant->id)
            ->where('category', 'sms')
            ->where('driver', 'twilio')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return false;
        }

        $config = $integration->config; // Decrypted automatically
        $client = new \Twilio\Rest\Client($config['account_sid'], $config['auth_token']);
        $client->messages->create($to, [
            'from' => $config['from_number'],
            'body' => $message,
        ]);

        return true;
    }

    public function requiresConfig(): bool { return true; }

    public function configFields(): array
    {
        return [
            ['key' => 'account_sid', 'label' => 'Account SID', 'type' => 'text'],
            ['key' => 'auth_token', 'label' => 'Auth Token', 'type' => 'password'],
            ['key' => 'from_number', 'label' => 'From Number', 'type' => 'text'],
        ];
    }
}
```

Register in your plugin:

```php
public function register(): void
{
    app(IntegrationManager::class)->registerDriver('sms', 'twilio', TwilioSmsProvider::class);
}
```

Once registered and configured by the tenant admin at Settings > Integrations, the `SmsService` will automatically use the active SMS provider for all outbound SMS (sequences, notifications, etc.).

### Using the SMS Service

Send SMS from anywhere in your plugin:

```php
use App\Services\SmsService;

$sms = app(SmsService::class);
$sms->send('+15551234567', 'Hello from InsulaCRM!');
```

### Using the Cache Service

The `CacheService` provides tenant-scoped caching:

```php
use App\Services\CacheService;

$cache = app(CacheService::class);

// Cache a value for 5 minutes
$value = $cache->remember('my_plugin.data', 300, function () {
    return DB::table('my_table')->count();
});

// Forget a cached value
$cache->forget('my_plugin.data');
```

### Using the Storage Service

The `StorageService` respects per-tenant storage configuration (local or S3):

```php
use App\Services\StorageService;

$storage = app(StorageService::class);

// Store a file
$path = $storage->store($uploadedFile, 'plugin-files');

// Get file URL
$url = $storage->url($path);

// Delete a file
$storage->delete($path);
```

### Integration Framework Overview

The CRM provides three layers of extensibility:

| Layer | Purpose | How to Use |
|-------|---------|------------|
| **Plugins** | Extend CRM features (UI, hooks, widgets) | Install plugin via ZIP or `plugins/` directory |
| **Integrations** | Connect external auth/service providers | Register driver via `IntegrationManager` |
| **REST API** | Programmatic data access | Use 20 API endpoints with tenant API key |
| **Webhooks** | Event-driven external notifications | Configure at Settings > Webhooks |

The Integration Framework uses typed contracts (`TwoFactorProviderInterface`, `SsoProviderInterface`, `SmsProviderInterface`) with a central `IntegrationManager` that handles driver registration, tenant-specific config resolution, and encrypted credential storage.

Additional services available to plugins:
- **SmsService** — Send SMS via the active provider
- **StorageService** — File operations on the tenant's configured disk (local/S3)
- **CacheService** — Tenant-scoped caching with automatic key namespacing

### Embeddable Web Form

The CRM includes a public lead capture form at `/forms/{api_key}` that can be embedded via iframe. Plugins that generate landing pages or marketing campaigns can direct form submissions to this URL, or use the `POST /api/v1/leads` endpoint directly for programmatic lead creation.

---

## HelloWorld Plugin Walkthrough

The included `hello-world` plugin is the simplest possible example. Here is a complete breakdown.

### File: plugins/hello-world/plugin.json

```json
{
    "name": "Hello World",
    "slug": "hello-world",
    "version": "1.0.0",
    "author": "System",
    "description": "A sample plugin demonstrating the plugin architecture."
}
```

This manifest registers the plugin with slug `hello-world`, matching the directory name.

### File: plugins/hello-world/boot.php

```php
<?php

use App\Facades\Hooks;

// Example: Log when a lead is created
Hooks::addAction('lead.created', function ($lead) {
    \Illuminate\Support\Facades\Log::info(
        "[HelloWorld Plugin] New lead created: {$lead->first_name} {$lead->last_name}"
    );
});

// Example: Log when a deal stage changes
Hooks::addAction('deal.stage_changed', function ($deal, $oldStage) {
    \Illuminate\Support\Facades\Log::info(
        "[HelloWorld Plugin] Deal #{$deal->id} moved from {$oldStage} to {$deal->stage}"
    );
});
```

### What Happens at Runtime

1. `PluginManager::bootAll()` finds the `hello-world` record in the `plugins` table with `is_active = true`.
2. It loads `plugins/hello-world/boot.php` via `require_once`.
3. Two callbacks are registered on the `lead.created` and `deal.stage_changed` hooks.
4. When a user creates a lead, `LeadController` calls `Hooks::doAction('lead.created', $lead)`.
5. The HookManager finds the registered callback and invokes it, writing a log entry to `storage/logs/laravel.log`.

You can verify the plugin is working by creating a lead and checking the log:

```bash
tail -f storage/logs/laravel.log | grep "HelloWorld"
```

---

## Advanced Plugin Example

Below is a more complete plugin that uses BasePlugin to add a sidebar link, a dashboard widget, custom routes, and hook listeners.

### plugins/deal-analytics/plugin.json

```json
{
    "name": "Deal Analytics",
    "slug": "deal-analytics",
    "version": "1.0.0",
    "author": "Your Company",
    "description": "Adds a deal analytics dashboard and tracks stage change metrics."
}
```

### plugins/deal-analytics/boot.php

```php
<?php

use App\Plugins\BasePlugin;
use App\Services\HookManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DealAnalyticsPlugin extends BasePlugin
{
    public function register(): void
    {
        // Register action hooks
        $this->hooks->addAction('deal.stage_changed', [$this, 'trackStageChange']);
        $this->hooks->addAction('lead.created', [$this, 'incrementLeadCounter']);
    }

    public function boot(): void
    {
        $this->addMenuItem('Deal Analytics', 'plugin.deal-analytics.index', 'fas fa-chart-line');
        $this->addDashboardWidget('deal-analytics::widget', 25);
        $this->addSettingsTab('Analytics', 'deal-analytics::settings');
        $this->loadRoutes(__DIR__ . '/routes/web.php');
        $this->registerMigrations(__DIR__ . '/database/migrations');
    }

    public function trackStageChange($deal, $oldStage): void
    {
        try {
            DB::table('deal_stage_metrics')->insert([
                'deal_id'   => $deal->id,
                'tenant_id' => $deal->tenant_id,
                'from_stage' => $oldStage,
                'to_stage'   => $deal->stage,
                'changed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("[DealAnalytics] Failed to track stage change: " . $e->getMessage());
        }
    }

    public function incrementLeadCounter($lead): void
    {
        try {
            DB::table('analytics_counters')
                ->where('tenant_id', $lead->tenant_id)
                ->where('metric', 'leads_created')
                ->increment('count');
        } catch (\Throwable $e) {
            Log::error("[DealAnalytics] Failed to increment lead counter: " . $e->getMessage());
        }
    }
}

// Bootstrap
$hooks = app(HookManager::class);
$manifest = json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
$plugin = new DealAnalyticsPlugin($hooks, __DIR__, $manifest);
$plugin->register();
$plugin->boot();
```

---

## Packaging and Distribution

Plugins are distributed as ZIP files containing the plugin directory.

### Creating a Plugin ZIP

```bash
cd plugins/
zip -r my-plugin-1.0.0.zip my-plugin/
```

The ZIP file should extract to a single directory matching the plugin slug:

```
my-plugin-1.0.0.zip
  my-plugin/
    plugin.json
    boot.php
    src/
    ...
```

### Installation Steps

1. Extract the ZIP into the `plugins/` directory so that `plugins/{slug}/plugin.json` exists.
2. Insert a record into the `plugins` database table:

```php
use App\Models\Plugin;

$manifest = json_decode(file_get_contents(base_path('plugins/my-plugin/plugin.json')), true);

Plugin::create([
    'tenant_id'    => $tenantId,
    'name'         => $manifest['name'],
    'slug'         => $manifest['slug'],
    'version'      => $manifest['version'],
    'author'       => $manifest['author'],
    'description'  => $manifest['description'],
    'is_active'    => true,
    'installed_at' => now(),
]);
```

3. The plugin will boot on the next request.

### Pre-Distribution Checklist

- [ ] `plugin.json` contains all required fields
- [ ] `slug` in `plugin.json` matches the directory name
- [ ] `boot.php` exists and is free of syntax errors
- [ ] All hook callbacks are wrapped in try/catch
- [ ] No hardcoded tenant IDs or file paths
- [ ] Migrations are idempotent (use `if not exists` or similar guards)
- [ ] No `dd()`, `dump()`, or `var_dump()` calls left in code
- [ ] Tested with plugin disabled to confirm the core CRM is unaffected

---

## Security Considerations

### Tenant Scoping

This is a multi-tenant CRM. Every database query in your plugin **must** be scoped to the current tenant. Never access data belonging to other tenants.

```php
// WRONG - accesses all tenants' data
$leads = DB::table('leads')->get();

// CORRECT - scope to current tenant
$leads = DB::table('leads')->where('tenant_id', auth()->user()->tenant_id)->get();
```

If you use Eloquent models that already apply tenant scoping via global scopes, this is handled automatically. But raw queries and the query builder require explicit tenant filtering.

### Error Handling

The `PluginManager` wraps the initial `require_once` of `boot.php` in a try/catch, so a broken plugin will not crash the entire application. However, **hook callbacks execute later during the request lifecycle** and are not individually wrapped by the core.

Always wrap your callback logic in try/catch:

```php
Hooks::addAction('lead.created', function ($lead) {
    try {
        // Your logic
    } catch (\Throwable $e) {
        \Log::error("[MyPlugin] Error handling lead.created: " . $e->getMessage());
    }
});
```

If your callback throws an uncaught exception, it will propagate up and may cause a 500 error for the user.

### Input Validation

Never trust data passed to your hooks without validation. Even though the core application validates input before firing hooks, other plugins could fire the same hooks with arbitrary data.

```php
Hooks::addAction('lead.created', function ($lead) {
    if (!$lead instanceof \App\Models\Lead) {
        return;
    }
    // Safe to proceed
});
```

### Filesystem Access

- Only read and write files within your plugin directory (`plugins/{slug}/`).
- Never modify core application files.
- Use Laravel's `Storage` facade for user-uploaded files rather than direct filesystem access.

### Avoid Blocking Operations

Hook callbacks run synchronously during the HTTP request. For long-running tasks (API calls, large data processing), dispatch a queued job instead:

```php
Hooks::addAction('deal.stage_changed', function ($deal, $oldStage) {
    \App\Jobs\SyncDealToExternalCRM::dispatch($deal, $oldStage);
});
```

### No Direct SQL Mutations on Core Tables

Prefer using Eloquent models and their built-in validation over raw `DB::statement()` calls against core CRM tables. If you must add columns, do so through a migration and test thoroughly.

---

## Troubleshooting

### Plugin is not loading

1. Verify the `plugins` table has a row with `is_active = 1` and the correct `slug`.
2. Confirm `plugins/{slug}/boot.php` exists on disk.
3. Check `storage/logs/laravel.log` for warnings like `Plugin {slug}: boot.php not found`.

### Hook callback is not firing

1. Confirm the hook name is spelled correctly (e.g., `lead.created`, not `leads.created`).
2. Check that the plugin's `is_active` is `true` in the database.
3. Verify the hook is actually fired in the code path you expect (see the [Available Action Hooks](#available-action-hooks) section for locations).
4. Add a `\Log::info()` as the first line of your callback to confirm registration.

### Plugin causes a 500 error

1. Check `storage/logs/laravel.log` for the exception trace.
2. Wrap all callback logic in try/catch.
3. Temporarily set `is_active = false` in the `plugins` table to disable the plugin without removing files.

### Checking loaded plugins

You can inspect which plugins are currently loaded:

```php
$manager = app(\App\Services\PluginManager::class);
$loaded = $manager->getLoaded(); // Returns array of Plugin models keyed by slug
```
