# InsulaCRM

A self-hosted, multi-tenant real estate wholesaler CRM built with Laravel 12 and the Tabler admin template. Designed for wholesaling teams, investors, and agencies who need a dedicated tool for managing motivated seller leads, property pipelines, deal tracking, and buyer disposition.

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-support-yellow?style=for-the-badge&logo=buy-me-a-coffee)](https://buymeacoffee.com/insulacrm)

---

## Features

### Lead Management
- Full CRUD with DataTables: search, sort, and filter by source, status, temperature, and agent
- 14 pipeline statuses from New through Closed Won/Lost (customizable via Settings)
- 11 lead sources: cold call, direct mail, website, referral, driving for dollars, PPC, SEO, social media, list import, API, other (customizable via Settings)
- Dual motivation scoring: automated system score (list stacking + temperature + engagement + property signals) and independent AI score displayed side-by-side
- Lead photos: drag & drop upload with lightbox, captions, and per-photo delete
- Inline activity editing and deletion via three-dot menu
- Task deletion and AI task suggestions with one-click add
- DNC (Do Not Contact) flag with system-wide enforcement

### Property Tracking
- Linked to leads: address, type, bedrooms, bathrooms, sq ft, year built, lot size, condition
- Distress markers: tax delinquent, pre-foreclosure, probate, code violation, vacant, and more
- Real-time financial calculator: ARV, repair estimate, MAO (Maximum Allowable Offer), assignment fee
- Address normalization service for deduplication accuracy
- Field Scout property submission form for drive-by assessments

### Deal Pipeline
- Accordion-style Kanban: collapsed stage rows showing deal count, total value, and fees
- Click to expand a stage and view deal cards in a responsive grid
- Drag-and-drop cards between expanded and collapsed stages (native HTML5 drag/drop)
- Search bar and agent filter for quick deal lookup
- Due diligence countdown with 48-hour urgency alerts
- Inline editing via slide-over panel: contract price, earnest money, inspection period, closing date
- Document uploads (PDF, JPG, PNG) per deal
- Automatic activity logging on stage changes
- Optimistic UI with toast notifications for instant feedback
- **Deal Activity Feed:** activity log with timeline on deal show page, add activities directly from the deal view via inline form

### Global Search
- Unified search across leads, deals, buyers, and properties
- Search bar in header navbar with live dropdown results (AJAX with debounce)
- Full results page at `/search` with grouped results by entity type
- Role-scoped: agents see only their own leads, field scouts cannot see deals, etc.
- Route: GET /search (SearchController)

### Two-Factor Authentication (2FA)
- **Pluggable 2FA system** — built-in TOTP as default, custom providers via plugins (Duo, Authy, SMS, etc.)
- `TwoFactorProviderInterface` contract for custom 2FA integrations
- Built-in TOTP: QR code setup with manual secret entry fallback, pure PHP (no external packages)
- 8 recovery codes generated on enable, universal across all providers
- Session-based challenge flow during login with provider-specific views
- **Admin 2FA enforcement**: toggle "Require 2FA for all users" at Settings > Integrations
- When enforced, users without 2FA are redirected to setup; disable button is hidden
- Encrypted secret and recovery code storage
- Routes: /two-factor/setup, /two-factor/challenge, /two-factor/verify

### Single Sign-On (SSO) Framework
- Pluggable SSO via `SsoProviderInterface` contract
- No built-in SSO providers — add via plugins (Google OAuth, Microsoft Azure AD, Okta SAML, etc.)
- SSO callback matches users by email within the tenant
- SSO respects 2FA: if enabled, user completes 2FA challenge after SSO
- Routes: /sso/{driver}/redirect, /sso/{driver}/callback

### Integration Framework
- Unified integration system for 2FA, SSO, and future service connectors (email, SMS)
- `IntegrationManager` central registry with pluggable driver architecture
- Per-tenant integration configuration with encrypted config storage
- Settings > Integrations tab: security settings, 2FA providers, SSO providers, custom integration guide
- Three integration methods: Plugins, REST API, and Webhooks
- Plugins register custom drivers via `IntegrationManager::registerDriver()`

### SMS Gateway Integration
- Pluggable SMS via `SmsProviderInterface` contract
- Built-in `LogSmsProvider` for development (logs to Laravel log)
- Plugins can register custom providers (Twilio, Vonage, etc.) via IntegrationManager
- `SmsService` facade for sending messages from anywhere in the application

### Security & Compliance
- **Security Headers:** X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, HSTS (non-local)
- **API Rate Limiting:** 60 requests/minute per API key with configurable rate limiter
- **API Request Logging:** `api_logs` table tracking method, path, status code, IP, user agent, and response duration
- **GDPR Data Export:** admin can export all user/contact data as JSON download
- **GDPR Data Deletion:** anonymize user accounts and contact records (removes PII, keeps stats)

### Email Template Editor
- Per-tenant HTML email templates with CRUD management
- Template variables: `{{name}}`, `{{email}}`, `{{company}}`, `{{date}}`
- Preview templates in a separate window
- Admin-only at Settings > Email Templates

### PDF Report Export
- Print-optimized HTML views for leads, pipeline, and team reports
- Professional print stylesheet with company header and generation timestamp
- Browser's "Save as PDF" via `window.print()` — no external PDF packages needed

### Docker Support
- Production-ready Dockerfile with PHP 8.2 FPM
- docker-compose.yml with nginx, MySQL 8.0, and Redis services
- Nginx configuration optimized for Laravel
- PHP config overrides (50MB upload, 256MB memory)

### Database Backup & Restore
- `php artisan backup:run` — creates compressed `.sql.gz` backups
- `php artisan backup:restore {filename}` — restores from backup with confirmation
- `php artisan backup:clean` — removes old backups (default 30 days)
- Scheduled daily cleanup at 01:00

### Caching Layer
- Tenant-scoped `CacheService` for dashboard KPIs and report data
- Supports Redis, database, or file cache drivers
- Automatic cache key namespacing by tenant ID
- Flush tenant cache on demand

### Onboarding Wizard
- 4-step guided setup for first-time admin after fresh install
- Steps: Company Profile > Invite Team > Create First Lead > All Done
- Skip option available at every step
- Only triggers for users created by the installer

### Dark Mode
- Tabler dark theme toggle via `data-bs-theme` attribute
- Toggle button in header navbar (moon/sun icon)
- Persisted per-user in database (`theme` column)
- Instant switch via JS + AJAX save

### Mobile Responsiveness
- Dedicated `mobile.css` with responsive breakpoints
- Touch-friendly targets (36px minimum) for buttons and form controls
- Horizontal scroll for tables and Kanban board on small screens
- Print-optimized styles for reports
- Tablet-specific adjustments for search and Kanban

### Configurable Cloud Storage
- Per-tenant storage disk setting (local or S3)
- `StorageService` helper for file operations respecting tenant config
- S3 configuration via `.env` variables (key, secret, region, bucket)
- Admin configurable at Settings > Storage

### API Documentation
- Interactive API docs page at `/api-docs` (admin only)
- OpenAPI 3.0 JSON spec download at `/api-docs/openapi.json`
- Grouped endpoints: Leads, Deals, Buyers, Properties, Activities, Stats
- Quick reference: base URL, auth, pagination, error format

### Calendar View
- Monthly calendar grid with previous/next/today navigation
- Events from tasks (due dates) and activities (meetings, calls)
- Color-coded event types: blue (tasks), green (meetings), purple (calls)
- Click event to navigate to related lead
- AJAX event loading by date range
- Role-scoped: agents see own events, admins see all, field scouts excluded

### Tags & Labels
- Polymorphic tagging system for leads and deals
- Color-coded tag badges (9 color options)
- Attach/detach tags via AJAX from lead and deal views
- Admin-only tag management at `/tags`
- Duplicate tag names automatically reuse existing tags

### Lead Kanban Board
- Visual board with columns for each lead status
- Native HTML5 drag-and-drop between columns
- Cards show name, phone, temperature, tags, and assigned agent
- Agent filter for admin users
- Role-scoped: agents see only their assigned leads
- Route: GET /leads/kanban

### Cash Buyer Database
- Full buyer CRUD with company info, contact details, and investment criteria
- JSON preference fields: property types, zip codes, states, asset classes
- Reliability scoring (decreases when buyers back out of deals)
- Automated deal-buyer matching when deals reach Dispositions stage
- Match scoring: zip code (+30), property type (+25), price range (+20), state (+15)

### List Import & Stacking
- CSV import with column mapping UI
- List types: tax delinquent, probate, code violation, pre-foreclosure, absentee owner, and more
- Automatic deduplication via normalized address matching
- Motivation score recalculation based on list stacking depth
- Stacked leads filter and badge for leads on 3+ lists

### Drip Sequences
- Multi-step sequence builder: SMS, email, call, voicemail, direct mail
- Configurable delay between steps (in days)
- **AI Generate:** one-click AI template generation per step, context-aware (step position, action type, previous step)
- Merge tags: `{first_name}`, `{last_name}`, `{address}`, `{company_name}`
- Enroll/unenroll leads from the lead detail page
- **Sequence enrollment widget** on lead detail: progress bar, step timeline, upcoming step preview with message
- Daily cron job processes active enrollments and fires appropriate actions
- DNC check before every outreach step

### DNC & TCPA Compliance
- System-wide DNC list management (phone and email)
- Single entry and bulk CSV import
- Automatic DNC blocking on all outreach actions
- Timezone-based contact restrictions (8 AM - 9 PM local time via ZIP lookup)

### Lead Distribution
- **Round Robin:** auto-assign leads evenly across acquisition agents
- **Shark Tank:** broadcast to all agents with flash notification, first claim wins
- **Hybrid:** broadcast with configurable claim window, auto-assign via round robin on expiry
- Database-level locking to prevent race conditions on claims

### Reporting & Analytics
- Admin dashboard with KPI cards, ApexCharts, and AJAX-loaded widgets
- Pipeline bottleneck widget: average days per stage with red alerts over 7 days
- Team performance leaderboard: leads contacted, offers made, deals closed
- Lead source ROI widget with cost-per-deal tracking
- Reports page with date/agent filters and CSV export
- Conversion funnel: leads > contacted > offer > contract > closed
- **Conversion Trend:** monthly leads vs closed deals with conversion rate over 6 months
- **Lead-to-Close Velocity:** average, minimum, and maximum days from lead to closed deal
- **Agent Comparison:** top 5 agents ranked by deals closed with visual progress bars

### REST API
- 20 RESTful endpoints under `/api/v1/` for leads, deals, buyers, properties, activities, and stats
- Per-tenant API key authentication via `X-API-Key` header
- Lead ingestion with automatic source resolution, duplicate detection, and auto-distribution
- Deal stage changes trigger activity logging, buyer matching, and plugin hooks
- Stats endpoint returns KPIs, pipeline breakdown, lead sources, and 6-month trends
- Embeddable web form for public lead capture (branded with tenant logo)
- Ready for Zapier, Make, custom integrations, and plugin-to-API communication

### Internationalization
- Locale-aware formatting via `TenantFormatHelper` (`Fmt` alias)
- 38 currencies, 50 countries, 50+ timezones grouped by region
- Imperial/metric measurement systems (sq ft ↔ m², acres ↔ hectares)
- Dynamic labels: State/Province, ZIP/Postal Code, TCPA/GDPR compliance law
- All views, calculators, CSV exports, and AI prompts use locale-aware formatting
- Public lead capture forms adapt to tenant locale

### Multi-Language Translation
- All 50 Blade views wrapped with Laravel `__()` helper (774 translatable strings)
- System enum labels translated at the source: lead statuses, lead sources, property types, conditions, distress markers, activity types, deal stages, role names
- 7 language packs included: English, Dutch, German, French, Spanish, Portuguese, Italian
- Per-tenant language setting in Settings > General
- **Built-in Language Editor** (Settings > Languages tab): view all language files, upload new ones, edit translations inline with search/filter and progress tracking
- Drop-in translation: add a `lang/xx.json` file to add any language — auto-discovered in settings
- Covers all UI: auth, navigation, leads, buyers, deals, dashboard, settings, installer, forms, reports, system enums

### Lead Photos
- Multiple photo upload per lead with drag & drop and click-to-browse
- FileReader preview with optional captions per photo
- Lightbox modal for full-size viewing
- Per-photo delete with confirmation
- Metadata display: uploader name and upload date

### AI Assistant (BYOK - Bring Your Own Key) — 19 Features
- Provider-agnostic: OpenAI, Anthropic (Claude), Google Gemini, Ollama (local), and any OpenAI-compatible endpoint (LM Studio, Lemonade, LocalAI, vLLM, etc.)
- **Draft Follow-Up:** AI-generated content for all 7 activity types — SMS, email, voicemail script, call script, direct mail, internal notes, and meeting prep
- **Caller identity injection:** drafts use the logged-in user's name and company name (no placeholder brackets)
- **Auto-fill subject:** "Use in Activity Form" populates both subject and body fields with contextual content
- **Summarize Notes:** Analyze activity timeline and produce concise summaries with motivation level and next steps
- **Deal Analysis:** Risk assessment, opportunity scoring, key concerns, and recommended actions
- **Deal Stage Advisor:** Stage-specific advice and recommended next actions for each deal
- **Buyer Outreach:** Auto-draft professional buyer notification emails tailored to deal and buyer preferences
- **Buyer Match Explanation:** AI explains why a buyer matches a particular deal
- **AI Lead Scoring:** Independent motivation assessment (saved separately from automated system score, no circular reasoning)
- **AI Task Suggestions:** Context-aware task recommendations with priority levels and one-click add
- **Smart Lead Qualification:** Auto-classifies lead temperature (hot/warm/cold) on ingestion via web form, API, or CSV import
- **Offer Strategy:** Property-specific negotiation tactics, offer structure, and pricing recommendations
- **Property Description:** Marketing-ready property descriptions for listings and buyer outreach
- **Sequence AI:** Per-step template generation + "Generate All Steps" for bulk sequence creation
- **CSV Auto-Mapping:** AI analyzes CSV headers and sample data to suggest column-to-field mappings with preview
- **Weekly Digest:** AI-powered dashboard summary of weekly KPIs, trends, and actionable recommendations
- **DNC Risk Check:** Compliance risk assessment with flags and recommendations for leads
- **Objection Library:** Tailored objection handling scripts based on lead context and common scenarios
- BYOK model: no cost to CRM operator, tenants pay their own AI API usage
- Auto-detect available models from provider API (dropdown with manual override)
- Settings > AI tab: provider selection, API key management, model configuration, connection testing

### User Profile
- Profile edit page at `/profile` accessible by all roles
- Update name, email, and password from one form
- Password change requires current password verification
- Routes: GET /profile (edit form), PUT /profile (save changes)

### Email Notifications
- 6 notification types: lead assigned, deal stage changed, due diligence warning, buyer match found, team member invited, sequence email
- All notifications are queued for background delivery
- Per-tenant notification preferences: toggle each type on/off at Settings > Notifications
- Sequence drip emails sent to leads with merge tag replacement ({first_name}, {last_name}, {company_name})
- Due diligence warnings sent to assigned agent and all tenant admins
- Buyer match alerts sent to admins and disposition agents

### Email / SMTP Settings
- Per-tenant SMTP configuration stored as `mail_settings` JSON on tenants table
- Settings > Email tab: SMTP host, port, encryption (TLS/SSL), username, password, from address, from name
- Test email button to verify SMTP connectivity
- TenantMiddleware dynamically applies mail config on each request

### In-App Notification Bell
- Real-time bell icon in header navbar with unread count badge
- Dropdown shows 10 most recent notifications with color-coded icons
- Click notification to navigate and auto-mark as read
- "Mark all read" and "View all notifications" links in dropdown
- Full notifications page at `/notifications` with pagination (25 per page)
- AJAX polling every 60 seconds for new notifications
- 5 notification types stored in database: lead assigned, deal stage changed, due diligence warning, buyer match found, team member invited
- Welcome email to new team members with login details

### CSV Export
- Export leads, buyers, and deals to CSV from any index page
- Respects current filters: search, status, source, temperature, agent
- Agent-scoped: non-admin users only export their own data
- Streaming download with `fputcsv()` for large datasets
- Filenames include date: `leads-export-2026-03-10.csv`

### Bulk Actions
- Leads: select multiple via checkboxes, then bulk assign to agent, change status, or delete
- Buyers: select multiple and bulk delete
- Select-all checkbox with visual count of selected items
- Agent scoping: regular agents can only act on their own leads
- Status changes fire the same events and hooks as individual updates

### Audit Log
- Searchable activity timeline at `/audit-log` (admin only)
- Tracks all CRUD operations: leads, buyers, deals, agents, settings, DNC, API, plugins
- Filter by action type, user, or free-text search
- Color-coded action badges: green (created), blue (updated), red (deleted), purple (stage changed)
- Shows before/after values for tracked changes
- CSV export with full audit trail
- Sidebar link for quick admin access

### Outbound Webhooks
- HTTP POST callbacks on CRM events: lead.created, lead.updated, deal.stage_changed, and more
- Wildcard (`*`) event subscription for all events
- HMAC-SHA256 payload signing with configurable secret key
- Auto-retry: 3 attempts with exponential backoff (10s, 60s, 300s)
- Auto-disable: webhooks are deactivated after 10 consecutive failures
- Management UI at Settings > Webhooks: create, toggle, delete, monitor failure counts
- Ready for Zapier, Make, n8n, and custom integrations

### Custom Fields
- Configurable lead statuses, lead sources, property types, activity types, and property conditions
- Add/remove/rename via Settings with per-source monthly cost tracking for lead sources
- Custom field slugs used consistently across UI, validation, API, and plugins

### Plugin Architecture
- File-based plugin system with `plugin.json` manifest
- ZIP upload installer with validation and migration support
- Hooks & Filters system: `lead.created`, `deal.stage_changed`, `activity.logged`, etc.
- Plugin extension points: menu items, dashboard widgets, settings tabs
- Plugins can leverage the REST API for external integrations
- HelloWorld sample plugin as developer reference
- Full developer documentation at `docs/plugin-development.md`

### Multi-Tenant Architecture
- Single database with automatic `tenant_id` scoping via Eloquent Global Scopes
- Each company sees only their own data
- Tenant-level settings: company name, country, timezone, currency, date format, measurement system, distribution method

### Role-Based Access (5 System Roles + Custom Roles)
| Role | Access |
|------|--------|
| **Admin** | Full access: leads, properties, pipeline, buyers, lists, sequences, reports, settings, plugins, team management, impersonation, system health |
| **Acquisition Agent** | Leads (own assigned), properties, pipeline, activities, tasks |
| **Disposition Agent** | Buyers, pipeline (dispositions focus), deal-buyer matching |
| **Field Scout** | Property submission form, property browsing |
| **Agent** | General agent role: leads, properties, pipeline |

Admins can create **custom roles** with granular permissions (33 permissions across 9 groups) at **Settings > Roles & Permissions**.

### Branding
- InsulaCRM logo with transparent background for auth and installer pages
- White logo variant for dark sidebar
- Whitelabel support: tenants can upload custom logo to replace default branding
- Auto-generated favicon

### Settings
- **General:** company name, country (50 countries), timezone (grouped by region), currency (38 currencies), date format, measurement system (imperial/metric), language (7 included, extensible via JSON files)
- **Notifications:** toggle email notifications per type (lead assigned, deal stage changed, due diligence warning, buyer match, team invite, sequence email)
- **Team:** invite members by email (with welcome notification), assign roles, activate/deactivate, impersonate
- **Distribution:** choose method (round robin / shark tank / hybrid), claim window, timezone routing
- **Lead Sources:** add/remove/rename custom lead sources, set monthly cost per source
- **Custom Fields:** property types, activity types with slug-based configuration
- **API:** generate/regenerate API key, enable/disable API access, embeddable web form link, full endpoint reference with code examples
- **AI:** provider selection (OpenAI/Anthropic/Gemini/Ollama), API key, model config, connection test, feature overview
- **Email:** SMTP host, port, encryption, username, password, from address, from name — with test email button
- **Compliance:** DNC list management, timezone restriction toggle
- **Languages:** built-in translation editor with file list, upload, inline key-value editing, search/filter, and progress tracking
- **Webhooks:** create outbound webhooks with URL, secret, and event subscriptions; toggle, monitor failures, auto-disable broken hooks
- **System:** health check (PHP version, Laravel version, DB connection, storage, queue driver), current app version

---

## Server Requirements

- **PHP** >= 8.2 with extensions: BCMath, Ctype, cURL, DOM, Fileinfo, GD, JSON, Mbstring, OpenSSL, PDO, PDO MySQL, Tokenizer, XML
- **MySQL** 8.0+ or MariaDB 10.6+
- **Composer** 2.x only if you plan to reinstall PHP dependencies, deploy from source, or use the development workflow
- **Apache** or **Nginx** with mod_rewrite enabled
- **Node.js** >= 18 and npm only if you plan to modify front-end assets or use the development workflow

---

## Installation

### Option A: Web Installer (Recommended)

1. Upload all files to your server in a project directory such as `/var/www/insulacrm`
2. Choose a deployment mode:
   - Recommended: point your root domain or subdomain document root to the project's `public/` directory. This is the simplest and preferred deployment path.
   - Supported for advanced users: install in a subfolder such as `/demo`, `/crm`, or `/abc`. This may require Apache or Nginx alias/rewrite configuration depending on the hosting environment.
   - Shared hosting fallback: upload into the target folder and keep the included root `index.php` and `.htaccess`, but the server must still route requests into the Laravel entrypoint correctly.
3. Create a MySQL database (e.g., `insulacrm`)
4. Ensure these directories are writable:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```
5. Visit `https://yourdomain.com/install` and follow the 5-step wizard:
   - **Step 1:** Server requirements check
     Includes PHP extensions, `.env` readiness, MySQL PDO support, writable paths, and detected install URL
   - **Step 2:** Database configuration
     Primary path: use the existing database user assigned to your app. Automatic DB-user creation is an advanced MariaDB-admin option.
   - **Step 3:** Company name and admin account setup
    - **Step 4:** Automatic migration and base data seeding
    - **Step 5:** Success - log in and start using the CRM

The packaged release already includes the production `vendor/` directory, so a standard install does not require running Composer.
It also includes the runtime dependency required for optional demo data.

Recommended deployment is via root domain or subdomain pointed to the application's `public/` directory.
Installation in a subfolder such as `/demo` or `/abc` is supported for advanced users, but may require Apache/Nginx alias or rewrite configuration depending on the hosting environment.
For subfolder installs, the final public URL should be `https://example.com/demo`, not `https://example.com/demo/public`. See [INSTALLATION.md](INSTALLATION.md) for Apache alias, Nginx, and shared-hosting examples.
The shipped root `index.php` and `.htaccess` are intended to remain safe across normal subfolder redeploys as well, so replacing the package should not require reapplying app-side rewrite tweaks.

### Option B: Manual Installation

```bash
cp .env.example .env
php artisan key:generate
```

If you are deploying from source or intentionally reinstalling dependencies, run:

```bash
composer install --no-dev --optimize-autoloader
```

Edit `.env` with your database credentials, then:

```bash
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\BaseSeeder
php artisan storage:link
```

Then create your tenant and admin user manually via Tinker or your preferred bootstrap path. The web installer normally writes the install marker automatically; standard upgrades should not require rerunning the installer or manually recreating `storage/installed.lock`.

---

## Post-Installation Setup

### Cron Job (Required)

A cron job is required for drip sequences, lead distribution auto-assignment, and due diligence countdown alerts:

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Windows (Task Scheduler):**
```
php C:\path\to\insulacrm\artisan schedule:run
```
Set to run every minute.

### Queue Worker (Recommended)

For background processing of email notifications, webhook delivery, buyer matching, CSV imports, and motivation score recalculation:

```bash
php artisan queue:work --daemon --tries=3 --timeout=60
```

For production, use a process manager like Supervisor to keep the worker running.

### File Storage

Deal documents are stored in `storage/app/deal_documents/`. Run the storage link command if not already done:

```bash
php artisan storage:link
```

### Plugins Directory

The installer creates the `plugins/` directory automatically. If installing manually, create it:

```bash
mkdir plugins
chmod 775 plugins
```

### Updates & Upgrade Path

Normal updates do **not** require reinstalling the CRM.

- The installed version is read from the root `VERSION` file
- A normal upgrade is a file-replacement and migration process, not a fresh install
- Admins can upload update ZIPs from **Settings > System**

If `storage/installed.lock` is missing but the database already contains the expected tenant and user data, the app recreates the marker automatically instead of forcing the installer again.

For the exact upgrade procedure, see [UPGRADE.md](UPGRADE.md).

---

## Demo Data

To load the full demo dataset (200 leads, 50 deals, 20 buyers, 6 users, etc.):

```bash
php artisan db:seed
```

### Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@demo.com | password |
| Acquisition Agent | agent@demo.com | password |
| Acquisition Agent | agent2@demo.com | password |
| Acquisition Agent | agent3@demo.com | password |
| Disposition Agent | disposition@demo.com | password |
| Field Scout | scout@demo.com | password |

Demo tenant: **Apex Wholesale Properties**

---

## Plugin Development

See `docs/plugin-development.md` for the full developer guide.

### Quick Start

1. Create a folder: `plugins/your-plugin/`
2. Add a `plugin.json` manifest:
   ```json
   {
     "name": "Your Plugin",
     "slug": "your-plugin",
     "version": "1.0.0",
     "author": "Your Name",
     "description": "What it does.",
     "min_crm_version": "1.0.0",
     "entry_class": "YourPlugin"
   }
   ```
3. Create `src/Plugin.php` extending `App\Plugins\BasePlugin`
4. Upload via Settings > Plugins or place directly in the `plugins/` directory

### Available Hooks

| Hook | Fired When | Payload |
|------|-----------|---------|
| `lead.created` | New lead created | `Lead $lead` |
| `lead.updated` | Lead record updated | `Lead $lead` |
| `lead.status_changed` | Lead status changes | `Lead $lead, string $oldStatus` |
| `deal.created` | New deal created | `Deal $deal` |
| `deal.stage_changed` | Deal moves to new stage | `Deal $deal, string $oldStage` |
| `activity.logged` | Activity saved to lead | `Activity $activity` |
| `buyer.notified` | Buyer notified of match | `Buyer $buyer, Deal $deal` |
| `sequence.step_executed` | Drip step fires | `Lead $lead, array $step` |

### Extension Points

- `addMenuItem(label, route, icon)` — Add sidebar navigation items
- `addDashboardWidget(view, position)` — Add dashboard widgets
- `addSettingsTab(label, view)` — Add settings tabs
- `registerMigrations(path)` — Register plugin database migrations

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Tabler Admin Template (Bootstrap 5, MIT licensed) |
| Charts | ApexCharts |
| Kanban | Native HTML5 Drag & Drop (accordion layout) |
| API | RESTful JSON API with per-tenant key auth |
| AI | OpenAI, Anthropic, Gemini, Ollama, Custom OpenAI-compatible (BYOK) |
| Database | MySQL 8.0+ |
| Auth | Laravel session-based authentication |
| Queue | Laravel Queues (database driver) |
| Storage | Laravel Storage (local disk, S3-ready) |

---

## File Structure

```
insulacrm/
├── app/
│   ├── Http/Controllers/     # Web controllers
│   ├── Http/Controllers/Api/ # REST API controllers (6 resource controllers)
│   ├── Http/Middleware/       # Tenant, Role, ApiKey middleware
│   ├── Models/                # Eloquent models with tenant scoping
│   ├── Notifications/          # Email + database notification classes (6 types)
│   ├── Jobs/                   # Queued jobs (CSV import, webhook dispatch)
│   ├── Plugins/               # BasePlugin, PluginManager, HookManager
│   └── Services/              # AddressNormalization, DNC, Distribution, Webhook, AiService
│       └── AiProviders/       # OpenAI, Anthropic, Gemini, Ollama provider classes
├── database/
│   ├── factories/             # Model factories for demo data
│   ├── migrations/            # All database migrations
│   └── seeders/
│       ├── BaseSeeder.php     # Essential data only (used by installer)
│       └── DatabaseSeeder.php # Full demo dataset
├── docs/
│   └── plugin-development.md  # Plugin developer guide
├── lang/                      # JSON translation files (en, nl, de, fr, es, pt, it)
├── plugins/                   # Plugin installation directory
│   └── hello-world/           # Sample plugin
├── public/images/             # Logo variants (logo.png, logo-white.png, favicon.png)
├── resources/views/
│   ├── components/dashboard/  # Standalone dashboard widget components
│   ├── dashboard/             # Role-specific dashboard views
│   ├── forms/                 # Embeddable lead capture web form
│   ├── install/               # 5-step installer wizard views
│   ├── layouts/               # App and auth layouts
│   └── ...                    # Module views (leads, pipeline, buyers, etc.)
└── routes/
    ├── api.php                # REST API routes (20 endpoints, api.key middleware)
    └── web.php                # Web routes with role-based middleware
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full release history.

---

## Support

For support, bug reports, and feature requests, please open an issue on GitHub.

## License

This project is licensed under the [MIT License](LICENSE).

