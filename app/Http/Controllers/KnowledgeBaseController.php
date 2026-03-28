<?php

namespace App\Http\Controllers;

use App\Services\BusinessModeService;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $articles = $this->getArticles();
        $search = $request->input('q');

        if ($search) {
            $search = strtolower($search);
            $articles = array_map(function ($category) use ($search) {
                $category['articles'] = array_filter($category['articles'], function ($article) use ($search) {
                    return str_contains(strtolower($article['title']), $search)
                        || str_contains(strtolower(strip_tags($article['body'])), $search)
                        || collect($article['tags'] ?? [])->contains(fn ($tag) => str_contains(strtolower($tag), $search));
                });
                return $category;
            }, $articles);
            $articles = array_filter($articles, fn ($cat) => !empty($cat['articles']));
        }

        return view('help.index', compact('articles', 'search'));
    }

    public function show(string $slug)
    {
        $allArticles = $this->getArticles();
        $flat = [];
        foreach ($allArticles as $category) {
            foreach ($category['articles'] as $article) {
                $article['category'] = $category['name'];
                $flat[] = $article;
            }
        }

        $currentIndex = null;
        $siblings = [];
        $currentCategory = null;

        foreach ($allArticles as $category) {
            foreach ($category['articles'] as $i => $article) {
                if ($article['slug'] === $slug) {
                    $article['category'] = $category['name'];
                    $currentCategory = $category;
                    $siblings = $category['articles'];

                    // Find position in flat list for prev/next
                    foreach ($flat as $fi => $fa) {
                        if ($fa['slug'] === $slug) {
                            $currentIndex = $fi;
                            break;
                        }
                    }

                    $prev = $currentIndex > 0 ? $flat[$currentIndex - 1] : null;
                    $next = ($currentIndex !== null && $currentIndex < count($flat) - 1) ? $flat[$currentIndex + 1] : null;

                    return view('help.show', compact('article', 'siblings', 'prev', 'next'));
                }
            }
        }

        abort(404);
    }

    protected function getArticles(): array
    {
        return [
            // ─── GETTING STARTED ─────────────────────────────────────────
            [
                'name' => __('Getting Started'),
                'icon' => 'rocket',
                'articles' => [
                    [
                        'slug' => 'first-steps',
                        'title' => __('First Steps After Installation'),
                        'summary' => __('Set up your CRM, configure your company, and invite your team.'),
                        'tags' => ['setup', 'onboarding', 'getting started', 'installation'],
                        'body' => '<h3>Welcome to InsulaCRM</h3>
<p>Congratulations on installing InsulaCRM! This guide will walk you through the essential first steps to get your ' . (BusinessModeService::isRealEstate() ? 'real estate brokerage' : 'real estate wholesaling business') . ' up and running.</p>

<h3>Step 1: Complete the Onboarding Wizard</h3>
<p>When you first log in as an administrator, you\'ll see the <strong>Onboarding Wizard</strong>. This guided setup helps you configure the essential settings:</p>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content"><strong>Company Profile</strong> &mdash; Set your company name, timezone, currency, and date format. These appear throughout the CRM and on exported documents.</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content"><strong>Invite Team Members</strong> &mdash; Add ' . (BusinessModeService::isRealEstate() ? 'listing agents and buyers agents' : 'acquisition agents, disposition agents, field scouts,') . ' or general agents to your account. Each will receive a login link via email.</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content"><strong>Create Your First Lead</strong> &mdash; Add your first ' . (BusinessModeService::isRealEstate() ? 'new lead or client inquiry' : 'motivated seller lead') . ' to get familiar with the lead management system.</div></div>
<p>You can skip the wizard and configure everything manually later in <strong>Settings</strong>.</p>

<h3>Step 2: Configure Your Settings</h3>
<p>Navigate to <strong>Settings</strong> in the sidebar to fine-tune your CRM:</p>
<table>
<thead><tr><th>Tab</th><th>What to Configure</th></tr></thead>
<tbody>
<tr><td>General</td><td>Company name, timezone, currency, date format, default lead status</td></tr>
<tr><td>Team</td><td>Invite users, assign roles, manage active/inactive members</td></tr>
<tr><td>Roles &amp; Permissions</td><td>Create custom roles, assign granular permissions via the permission matrix</td></tr>
<tr><td>Distribution</td><td>Choose how new leads are assigned (Round Robin, Shark Tank, or Hybrid)</td></tr>
<tr><td>Email</td><td>SMTP settings so the CRM can send emails on your behalf</td></tr>
<tr><td>Webhooks</td><td>Outbound HTTP callbacks on CRM events with HMAC signing</td></tr>
<tr><td>Languages</td><td>View, upload, and edit translation files for multi-language support</td></tr>
<tr><td>API</td><td>Generate an API key to receive leads from external sources</td></tr>
<tr><td>AI</td><td>Connect your AI provider (OpenAI, Claude, Gemini, Ollama) for smart features</td></tr>
<tr><td>System</td><td>Review health checks, current version, and latest release metadata from the website</td></tr>
</tbody>
</table>

<h3>Step 3: Set Up Your Lead Sources</h3>
<p>Before adding leads, configure your lead sources in <strong>Settings &gt; Custom Fields</strong>. Default sources include ' . (BusinessModeService::isRealEstate() ? 'Website, Referral, Open House, Sign Call, PPC, SEO, Social Media, Zillow, Realtor.com, MLS, Sphere of Influence, and Past Client' : 'Cold Call, Direct Mail, Website, Referral, Driving for Dollars, PPC, SEO, Social Media, and List Import') . '. Add custom sources to match your marketing channels.</p>

<h3>Step 4: Enable Your API (Optional)</h3>
<p>If you receive leads from Zapier, landing pages, or ' . (BusinessModeService::isRealEstate() ? 'client communication tools' : 'skip tracing tools') . ', go to <strong>Settings &gt; API</strong> and click <strong>Generate API Key</strong>. This gives you:</p>
<ul>
<li>A REST API endpoint for pushing leads programmatically</li>
<li>An embeddable web form URL you can share or embed on your website</li>
<li>Webhook support for real-time event notifications</li>
</ul>

<h3>Step 5: Invite Your Team</h3>
<p>Go to <strong>Settings &gt; Team</strong> to add team members. Choose the right role for each person (see the <a href="' . url('/help/user-roles') . '">User Roles</a> article for details), set an initial password, and save the user.</p>

<h3>Step 6: Check Your Version &amp; Update Status</h3>
<p>Open <strong>Settings &gt; System</strong> to review the current installed version and system health information.</p>

<div class="kb-callout-success kb-callout">
<strong>You\'re all set!</strong> Start adding leads, building your pipeline, and closing ' . (BusinessModeService::isRealEstate() ? 'transactions' : 'deals') . '. Use the sidebar navigation to access all features.
</div>',
                    ],
                    [
                        'slug' => 'updating-insulacrm',
                        'title' => __('Updating InsulaCRM'),
                        'summary' => __('How normal upgrades work, what to preserve, and why a reinstall is not required.'),
                        'tags' => ['upgrade', 'update', 'version', 'release', 'maintenance'],
                        'body' => '<h3>Normal Upgrades Do Not Require Reinstalling</h3>
<p>A standard InsulaCRM upgrade is a file-replacement and migration process, not a fresh installation.</p>

<h3>Before You Upgrade</h3>
<ul>
<li>If you have a staging environment, test the release there before touching production</li>
<li>Confirm your plugins, integrations, custom automations, and background jobs still behave as expected</li>
<li>Back up your database</li>
<li>Back up your <code>.env</code> file</li>
<li>Back up <code>storage/</code>, uploads, and any custom plugins</li>
</ul>

<h3>Upgrade Steps</h3>
<p>The preferred path is now the built-in updater in <strong>Settings &gt; System</strong>.</p>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Upload the official InsulaCRM release ZIP in the <strong>Safe Update Manager</strong>.</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Review the staged update warnings before applying it.</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Click <strong>Snapshot, Backup, and Apply Update</strong>. InsulaCRM will create a fresh database backup and a recovery snapshot automatically before patching the app.</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">The updater preserves <code>.env</code>, <code>storage/</code>, <code>public/storage</code>, and <code>plugins/</code>, then runs migrations and a post-update health check.</div></div>
<div class="kb-step"><span class="kb-step-num">5</span><div class="kb-step-content">After the staging environment passes, apply the same release to production during a low-risk maintenance window.</div></div>
<div class="kb-step"><span class="kb-step-num">6</span><div class="kb-step-content">If you cannot use the in-app updater, fall back to the manual file-replacement process from the upgrade guide.</div></div>

<h3>Manual Upgrade &amp; OPcache</h3>
<p>If you upgrade by replacing files manually (rsync, scp, or unzipping a release), you must flush PHP OPcache after syncing the new files. Otherwise PHP may continue serving stale compiled bytecode from the previous version, which can cause 500 errors.</p>
<p>The easiest way is to run the included deployment helper:</p>
<pre><code>bash scripts/deploy.sh</code></pre>
<p>This runs migrations, clears Laravel caches, and reloads PHP-FPM to flush OPcache. If you prefer to do it manually, restart or reload your PHP-FPM service after syncing files:</p>
<pre><code>sudo systemctl reload php8.4-fpm</code></pre>
<p>The built-in Safe Update Manager handles OPcache flushing automatically.</p>

<h3>What Recovery Snapshots Are For</h3>
<p>Recovery snapshots are point-in-time restore points created immediately before the update starts. Use them to return the CRM to the last known-good state if an upgrade fails badly.</p>
<p>The best time to create a snapshot is right before applying the update. That keeps the amount of newer code and data you would lose during a restore as small as possible.</p>
<p>Administrators can also create manual recovery snapshots from <strong>Settings &gt; System</strong> before risky maintenance, custom development, or major configuration changes.</p>
<p>When you create, apply, or restore a snapshot or update from the UI, keep the page open until the action finishes. InsulaCRM now shows a waiting overlay and blocks duplicate submits so the same action is not triggered multiple times by accident.</p>
<p>Snapshot restores support both <code>.sql</code> and <code>.sql.gz</code> backups on Windows and Linux. Windows users do not need an external <code>gunzip</code> binary for restores triggered by the product.</p>

<h3>Version &amp; Update Visibility</h3>
<p>Open <strong>Settings &gt; System</strong> to compare your installed version against the latest release metadata published on the website. The version reported there comes from the root <code>VERSION</code> file packaged with the CRM.</p>

<h3>Installed Marker Recovery</h3>
<p>If <code>storage/installed.lock</code> is missing but the application still points to the correct production database and that database already contains the expected tenant and user records, InsulaCRM recreates the marker automatically instead of forcing the installer again.</p>

<div class="kb-callout-warning kb-callout">
<strong>Important:</strong> Do not rerun the installer for a normal upgrade. The Safe Update Manager reduces risk, but it does not replace testing production upgrades on staging first when staging is available. Restoring a recovery snapshot also replaces newer code and database changes created after the snapshot time.
</div>',
                    ],
                    [
                        'slug' => 'installer-troubleshooting',
                        'title' => __('Installer Troubleshooting'),
                        'summary' => __('How to fix the most common installer blockers such as .env, permissions, and PHP extension issues.'),
                        'tags' => ['installer', 'troubleshooting', 'permissions', '.env', 'php extensions'],
                        'body' => '<h3>Overview</h3>
<p>If the installer shows <strong>Action Required</strong>, it means InsulaCRM found something the server needs before setup can continue. The most common issues are a missing <code>.env</code> file, unwritable folders, or missing PHP extensions.</p>

<h3>How to Create the .env File</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Open the application root and locate <code>.env.example</code>.</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">On Linux or macOS, run <code>ls -la</code> first so hidden dotfiles are visible in the directory listing.</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Create a copy of that file and rename the copy to <code>.env</code>.</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">If <code>.env.example</code> is missing, re-upload the release package or create <code>.env</code> manually using the sample values from the installation guide.</div></div>
<div class="kb-step"><span class="kb-step-num">5</span><div class="kb-step-content">Refresh the installer and run the checks again.</div></div>
<pre>ls -la
cp .env.example .env</pre>

<h3>How to Make .env Writable</h3>
<p>The installer must be able to save the application URL, generated app key, and database credentials.</p>
<pre>chmod 664 .env
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .env storage bootstrap/cache</pre>
<p>If your web server user is not <code>www-data</code>, replace it with the correct user for your server. On Linux XAMPP local installs under <code>/opt/lampp</code>, Apache commonly runs as <code>daemon</code>, so for local testing you can use <code>chmod 666 .env</code> and <code>chmod -R 777 storage bootstrap/cache plugins</code> instead.</p>
<p>If <code>chown</code> fails with <code>Operation not permitted</code>, you are not running with enough privileges to change ownership. Use a sudo-capable account, or ask your server administrator to reset ownership for the file. If you were just added to the <code>sudo</code> group, log out and back in before retrying.</p>

<h3>How to Fix Folder Permissions</h3>
<p>Laravel must be able to write to <code>storage/</code> and <code>bootstrap/cache</code>. Plugin installs also require a writable <code>plugins/</code> folder.</p>
<pre>chmod -R 775 storage bootstrap/cache plugins
chown -R www-data:www-data storage bootstrap/cache plugins</pre>
<p>For Linux XAMPP local testing, use <code>chmod -R 777 storage bootstrap/cache plugins</code> if Apache is running as <code>daemon</code>.</p>
<p>If <code>chmod</code> succeeds but the installer still reports the folder as not writable, the remaining problem is ownership rather than mode bits. In that case, the <code>chown</code> step requires sudo or server-admin access.</p>

<h3>How to Enable MySQL PDO</h3>
<p>InsulaCRM requires the <code>pdo_mysql</code> extension for the guided installer.</p>
<h4>Ubuntu / Debian</h4>
<pre>sudo apt install -y php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-mysql
sudo systemctl restart apache2 php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-fpm</pre>
<h4>RHEL / AlmaLinux / Rocky</h4>
<pre>sudo dnf install -y php-mysqlnd
sudo systemctl restart httpd php-fpm</pre>
<h4>XAMPP</h4>
<p>Open <code>C:\\xampp\\php\\php.ini</code>, enable <code>extension=pdo_mysql</code>, save the file, and restart Apache.</p>

<h3>When You See a Generic 500 Error</h3>
<p>If the installer fails before it can render, check whether the web server can write <code>storage/logs/laravel.log</code>. On Linux test servers, this is a common reason the installer fails before the UI loads.</p>

<div class="kb-callout kb-callout-warning">
<strong>Tip:</strong> If your environment is unusual or managed by a control panel, fix permissions first, then refresh the requirements step before changing anything else.
</div>',
                    ],
                    [
                        'slug' => 'user-roles',
                        'title' => __('User Roles & Permissions'),
                        'summary' => __('Understand what each role can access and how to assign them.'),
                        'tags' => ['roles', 'permissions', 'team', 'access control'],
                        'body' => '<h3>Overview</h3>
<p>InsulaCRM uses a role-based access control system with <strong>' . (BusinessModeService::isRealEstate() ? '4' : '5') . ' system roles</strong> and support for <strong>custom roles</strong>. Each role determines which sections of the CRM a user can see and interact with. Roles are assigned when inviting a user and can be changed by an admin at any time.</p>

<h3>Role Reference</h3>
' . (BusinessModeService::isRealEstate() ? '<table>
<thead><tr><th>Role</th><th>Description</th><th>Access Level</th></tr></thead>
<tbody>
<tr><td><strong>Admin</strong></td><td>Full system administrator</td><td>Everything &mdash; leads, transactions, clients, reports, settings, plugins, team management, impersonation, system health, audit log, bug reports</td></tr>
<tr><td><strong>Agent</strong></td><td>General-purpose agent</td><td>Leads, properties, calendar, tasks, activities, transactions, clients.</td></tr>
<tr><td><strong>Listing Agent</strong></td><td>Focuses on seller-side transactions and listings</td><td>Leads, properties, listings, calendar, tasks, activities. Manages seller relationships and property marketing.</td></tr>
<tr><td><strong>Buyers Agent</strong></td><td>Focuses on buyer-side transactions</td><td>Clients, transactions, showings, calendar, tasks. Helps clients find and purchase properties.</td></tr>
</tbody>
</table>' : '<table>
<thead><tr><th>Role</th><th>Description</th><th>Access Level</th></tr></thead>
<tbody>
<tr><td><strong>Admin</strong></td><td>Full system administrator</td><td>Everything &mdash; leads, deals, buyers, reports, settings, plugins, team management, impersonation, system health, audit log, bug reports</td></tr>
<tr><td><strong>Agent</strong></td><td>General-purpose agent</td><td>Leads, properties, calendar, tasks, activities. Similar to Acquisition Agent.</td></tr>
<tr><td><strong>Acquisition Agent</strong></td><td>Focuses on finding and qualifying leads</td><td>Leads, properties, calendar, tasks, activities. Cannot access buyers, pipeline, or settings.</td></tr>
<tr><td><strong>Disposition Agent</strong></td><td>Focuses on closing deals and managing buyers</td><td>Pipeline/deals, buyers, calendar, tasks. Cannot directly manage leads.</td></tr>
<tr><td><strong>Field Scout</strong></td><td>Drives for dollars and submits properties</td><td>Dashboard and property submissions only. Cannot access leads, deals, buyers, or settings.</td></tr>
</tbody>
</table>') . '

<h3>What Each Role Can Access</h3>
<h4>Admin</h4>
<ul>
<li>All features available in the CRM</li>
<li>Team management: invite/deactivate users, reset 2FA, impersonate users</li>
<li>System configuration: all settings tabs, plugins, API, storage, backups, GDPR</li>
<li>Reports, audit log, bug reports, API documentation</li>
<li>Can create other admin users</li>
</ul>

' . (BusinessModeService::isRealEstate() ? '<h4>Listing Agent / Agent</h4>
<ul>
<li>Lead management: create, view, edit, update status, log activities</li>
<li>Properties: view and manage property records and listings</li>
<li>Tasks and calendar</li>
<li>AI features on leads (if AI is enabled)</li>
<li>Kanban board for lead pipeline visualization</li>
<li>Bulk actions on leads (status change, assign, export)</li>
</ul>

<h4>Buyers Agent</h4>
<ul>
<li>Transaction pipeline: view and manage transactions, update stages</li>
<li>Client database: create, edit, import, export clients</li>
<li>Client matching and notification from transaction panels</li>
<li>Showings and calendar</li>
<li>Tasks and activities</li>
</ul>' : '<h4>Acquisition Agent / Agent</h4>
<ul>
<li>Lead management: create, view, edit, update status, log activities</li>
<li>Properties: view and manage property records</li>
<li>Tasks and calendar</li>
<li>AI features on leads (if AI is enabled)</li>
<li>Kanban board for lead pipeline visualization</li>
<li>Bulk actions on leads (status change, assign, export)</li>
</ul>

<h4>Disposition Agent</h4>
<ul>
<li>Deal pipeline: view and manage deals, update stages, upload documents</li>
<li>Buyer database: create, edit, import, export buyers</li>
<li>Buyer matching and notification from deal panels</li>
<li>Calendar and tasks</li>
<li>Cannot access the leads list directly</li>
</ul>

<h4>Field Scout</h4>
<ul>
<li>Dashboard with a simplified view</li>
<li>Property submission form (for driving-for-dollars discoveries)</li>
<li>View submitted properties</li>
<li>Cannot access leads, deals, buyers, reports, or settings</li>
</ul>') . '

<h3>Custom Roles &amp; Permissions</h3>
<p>Admins can create <strong>custom roles</strong> with fine-grained permissions at <strong>Settings &gt; Roles &amp; Permissions</strong>. The system includes 33 individual permissions across 9 groups:</p>
<ul>
<li><strong>Leads</strong> &mdash; view, create, edit, delete, import, export</li>
<li><strong>Properties</strong> &mdash; view, create, edit</li>
<li><strong>Deals</strong> &mdash; view, create, edit, manage documents</li>
<li><strong>' . (BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers') . '</strong> &mdash; view, create, edit, match</li>
<li><strong>Calendar, Reports, Sequences, Lists/Tags</strong> &mdash; view and manage</li>
<li><strong>Settings</strong> &mdash; manage settings, manage team, manage plugins</li>
</ul>
<p>System roles (Admin, Agent, etc.) cannot be deleted but their permissions can be customized. Custom roles are tenant-scoped and start with no permissions.</p>

<h3>Assigning Roles</h3>
<p>When inviting a new team member from <strong>Settings &gt; Team</strong>, select their role from the dropdown (includes both system roles and any custom roles you\'ve created). To change an existing user\'s role, admins can edit the user\'s profile. Role changes take effect immediately.</p>

<div class="kb-callout">
<strong>Tip:</strong> Use the principle of least privilege. Give each user only the access they need for their job function. Custom roles let you create exactly the right access level.
</div>',
                    ],
                    [
                        'slug' => 'navigating-the-interface',
                        'title' => __('Navigating the Interface'),
                        'summary' => __('Learn the sidebar, search, notifications, and how pages are organized.'),
                        'tags' => ['navigation', 'sidebar', 'search', 'UI', 'dashboard'],
                        'body' => '<h3>Sidebar Navigation</h3>
<p>The left sidebar is your main navigation. It shows only the pages your role has access to. Common items include:</p>
<ul>
<li><strong>Dashboard</strong> &mdash; KPI cards, charts, recent activity, and quick actions</li>
<li><strong>Leads</strong> &mdash; Your lead database with list and Kanban views</li>
<li><strong>Properties</strong> &mdash; Property records linked to leads</li>
<li><strong>Calendar</strong> &mdash; Tasks and activities on a calendar view</li>
<li><strong>Pipeline</strong> &mdash; ' . (BusinessModeService::isRealEstate() ? 'Transaction pipeline Kanban board' : 'Deal pipeline Kanban board') . '</li>
<li><strong>' . (BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers') . '</strong> &mdash; ' . (BusinessModeService::isRealEstate() ? 'Client database' : 'Cash buyer database') . '</li>
<li><strong>Reports</strong> &mdash; Analytics and performance dashboards</li>
<li><strong>Settings</strong> &mdash; All configuration (admin only)</li>
<li><strong>Help</strong> &mdash; This knowledge base</li>
</ul>

<h3>Global Search</h3>
<p>The search bar in the top-right corner searches across <strong>leads, ' . (BusinessModeService::isRealEstate() ? 'transactions, clients' : 'deals, buyers') . ', and properties</strong> simultaneously. Type at least 2 characters to see results. Click a result to jump directly to that record.</p>

<h3>Notifications</h3>
<p>The bell icon shows real-time notifications for:</p>
<ul>
<li>New leads assigned to you</li>
<li>Deal stage changes</li>
<li>Task reminders</li>
<li>' . (BusinessModeService::isRealEstate() ? 'Client match alerts' : 'Buyer match alerts') . '</li>
<li>Sequence step completions</li>
</ul>
<p>Click <strong>Mark all read</strong> to clear notifications, or click individual notifications to navigate to the relevant record.</p>

<h3>Dark Mode</h3>
<p>Click the <strong>sun/moon icon</strong> in the top-right corner to toggle between light and dark themes. Your preference is saved automatically and persists across sessions.</p>

<h3>Mobile Responsive</h3>
<p>InsulaCRM is fully responsive. On mobile devices, the sidebar collapses into a hamburger menu. All features work on phones and tablets, including the Kanban boards, forms, and data tables.</p>

<h3>Impersonation (Admin)</h3>
<p>Admins can impersonate any user to troubleshoot issues. Go to <strong>Settings &gt; Team</strong> and click the impersonate icon next to a user. A yellow banner appears at the top of the page while impersonating. Click <strong>Stop Impersonation</strong> to return to your admin account.</p>',
                    ],
                ],
            ],

            // ─── LEAD MANAGEMENT ─────────────────────────────────────────
            [
                'name' => __('Lead Management'),
                'icon' => 'users',
                'articles' => [
                    [
                        'slug' => 'creating-leads',
                        'title' => __('Creating & Managing Leads'),
                        'summary' => __('Add leads manually, view details, update statuses, and manage photos.'),
                        'tags' => ['leads', 'contacts', 'create', 'edit', 'photos'],
                        'body' => '<h3>Creating a New Lead</h3>
<p>Navigate to <strong>Leads</strong> in the sidebar and click the <strong>Add Lead</strong> button. Fill in the following information:</p>

<h4>Required Fields</h4>
<ul>
<li><strong>First Name</strong> and <strong>Last Name</strong> &mdash; The ' . (BusinessModeService::isRealEstate() ? 'contact\'s' : 'seller\'s') . ' name</li>
<li><strong>Status</strong> &mdash; Starting status (defaults to "New")</li>
</ul>

<h4>Contact Information</h4>
<ul>
<li><strong>Phone</strong> &mdash; Primary phone number. The system checks this against the DNC (Do Not Contact) list automatically.</li>
<li><strong>Email</strong> &mdash; Email address for correspondence and sequences</li>
<li><strong>Mailing Address</strong> &mdash; Separate from the property address</li>
</ul>

<h4>Property Details</h4>
<ul>
<li><strong>Property Address</strong> &mdash; Street, city, state, zip code</li>
<li><strong>Property Type</strong> &mdash; Single Family, Multi-Family, Land, Commercial, etc.</li>
<li><strong>Bedrooms / Bathrooms / Square Footage</strong> &mdash; Property specs</li>
<li><strong>' . (BusinessModeService::isRealEstate() ? 'List Price</strong> and <strong>Estimated Value' : 'Estimated Value</strong> and <strong>Asking Price') . '</strong></li>
</ul>

<h4>Additional Fields</h4>
<ul>
<li><strong>Lead Source</strong> &mdash; Where the lead came from (' . (BusinessModeService::isRealEstate() ? 'Open House, Referral, Zillow, etc.' : 'Direct Mail, PPC, etc.') . ')</li>
<li><strong>Assigned To</strong> &mdash; Which agent handles this lead (auto-assigned if distribution is enabled)</li>
<li><strong>Notes</strong> &mdash; Free-text notes about the ' . (BusinessModeService::isRealEstate() ? 'client\'s situation' : 'seller\'s situation') . '</li>
<li><strong>Tags</strong> &mdash; Categorize leads with custom tags</li>
</ul>

<h3>The Lead Detail Page</h3>
<p>Click any lead to open its detail page. This is your command center for that lead:</p>
<ul>
<li><strong>Contact Info</strong> &mdash; All ' . (BusinessModeService::isRealEstate() ? 'contact' : 'seller') . ' details with click-to-call and click-to-email</li>
<li><strong>Property Details</strong> &mdash; Full property record with photos</li>
<li><strong>Activity Timeline</strong> &mdash; Chronological log of all calls, emails, notes, meetings, and site visits</li>
<li><strong>Tasks</strong> &mdash; To-do items linked to this lead</li>
<li><strong>Motivation Score</strong> &mdash; Visual gauge showing ' . (BusinessModeService::isRealEstate() ? 'client readiness' : 'seller motivation') . ' (0-100)</li>
<li><strong>AI Actions</strong> &mdash; Draft follow-up, summarize notes, score lead (if AI enabled)</li>
</ul>

<h3>Lead Photos</h3>
<p>Upload property photos directly from the lead detail page. Click the <strong>Upload Photos</strong> button in the property section. Supported formats: JPG, PNG, WEBP. Photos are stored using your configured storage driver (local or S3).</p>

<h3>Editing & Deleting Leads</h3>
<p>Click <strong>Edit</strong> on the lead detail page to update any field. To delete a lead, click the <strong>Delete</strong> button and confirm. Deletion is permanent and also removes associated activities, tasks, and photos.</p>

<div class="kb-callout-warning kb-callout">
<strong>DNC Check:</strong> When adding or editing a lead\'s phone number, the system automatically checks it against the DNC list. You\'ll see a warning if the number is flagged.
</div>',
                    ],
                    [
                        'slug' => 'lead-statuses',
                        'title' => __('Lead Statuses & Workflow'),
                        'summary' => __('Understand how leads move through the sales pipeline stages.'),
                        'tags' => ['status', 'workflow', 'pipeline', 'stages'],
                        'body' => '<h3>Status Progression</h3>
<p>Every lead has a status that tracks where they are in your sales pipeline. Leads typically progress through these statuses:</p>

' . (BusinessModeService::isRealEstate() ? '<table>
<thead><tr><th>Status</th><th>Description</th><th>Typical Actions</th></tr></thead>
<tbody>
<tr><td><strong>New</strong></td><td>Just entered the system</td><td>Initial outreach, first contact</td></tr>
<tr><td><strong>Inquiry</strong></td><td>Prospect has reached out or been contacted</td><td>Respond to inquiry, qualify interest</td></tr>
<tr><td><strong>Consultation</strong></td><td>Meeting or call has occurred</td><td>Needs assessment, market presentation</td></tr>
<tr><td><strong>Active Client</strong></td><td>Signed engagement, actively working together</td><td>Property search, listing prep, showings</td></tr>
<tr><td><strong>Nurture</strong></td><td>Not ready now, stay in touch</td><td>Drip campaigns, periodic check-ins</td></tr>
<tr><td><strong>Closed Won</strong></td><td>Transaction completed successfully</td><td>Final documentation, referral request</td></tr>
<tr><td><strong>Closed Lost</strong></td><td>Lead did not convert</td><td>Log reason, potentially re-engage later</td></tr>
<tr><td><strong>Dead</strong></td><td>Permanently disqualified</td><td>No further action</td></tr>
</tbody>
</table>' : '<table>
<thead><tr><th>Status</th><th>Description</th><th>Typical Actions</th></tr></thead>
<tbody>
<tr><td><strong>New</strong></td><td>Just entered the system</td><td>Initial outreach, first call attempt</td></tr>
<tr><td><strong>Contacted</strong></td><td>You\'ve spoken with the seller</td><td>Qualifying questions, assess motivation</td></tr>
<tr><td><strong>Negotiating</strong></td><td>Active discussions on terms</td><td>Property evaluation, comparable analysis, offer prep</td></tr>
<tr><td><strong>Offer Made</strong></td><td>You\'ve submitted an offer</td><td>Waiting for response, follow-up, negotiation</td></tr>
<tr><td><strong>Under Contract</strong></td><td>Offer accepted, contract signed</td><td>Due diligence, title search, find buyers &mdash; a Deal is typically created at this stage</td></tr>
<tr><td><strong>Closed Won</strong></td><td>Deal completed successfully</td><td>Final documentation, payment</td></tr>
<tr><td><strong>Closed Lost</strong></td><td>Lead did not convert</td><td>Log reason, potentially re-engage later</td></tr>
<tr><td><strong>On Hold</strong></td><td>Temporarily paused</td><td>Seller not ready, follow up later</td></tr>
<tr><td><strong>DNC</strong></td><td>Do Not Contact</td><td>No further outreach allowed</td></tr>
</tbody>
</table>') . '

<h3>Changing Status</h3>
<p>There are several ways to update a lead\'s status:</p>
<ul>
<li><strong>Lead Detail Page</strong> &mdash; Click the status dropdown and select the new status</li>
<li><strong>Leads List</strong> &mdash; Use the quick-action dropdown on any row</li>
<li><strong>Kanban Board</strong> &mdash; Drag and drop cards between columns</li>
<li><strong>Bulk Actions</strong> &mdash; Select multiple leads and change their status at once</li>
</ul>

<h3>Automatic Status Triggers</h3>
<p>The system fires events on status changes that can trigger:</p>
<ul>
<li>Webhook notifications to external services</li>
<li>Plugin hooks for custom behavior</li>
<li>Audit log entries for compliance tracking</li>
<li>Sequence enrollment/unenrollment</li>
</ul>',
                    ],
                    [
                        'slug' => 'lead-kanban',
                        'title' => __('Lead Kanban Board'),
                        'summary' => __('Visualize your leads in a drag-and-drop Kanban board.'),
                        'tags' => ['kanban', 'board', 'drag and drop', 'visual'],
                        'body' => '<h3>Accessing the Kanban Board</h3>
<p>From the <strong>Leads</strong> page, click the <strong>Kanban</strong> button in the header to switch from list view to board view. You can switch back at any time.</p>

<h3>How It Works</h3>
<p>The Kanban board displays leads as cards organized into columns by status. Each column represents a lead status.</p>

<h4>Card Information</h4>
<p>Each card shows at a glance:</p>
<ul>
<li>Lead name and phone number</li>
<li>Property address (if available)</li>
<li>Assigned agent</li>
<li>Lead source badge</li>
<li>Motivation score indicator</li>
</ul>

<h3>Moving Leads</h3>
<p>To change a lead\'s status, simply <strong>drag the card</strong> from one column and <strong>drop it</strong> into another. The status updates instantly and triggers all associated events (webhooks, audit log, plugin hooks).</p>

<div class="kb-callout">
<strong>Tip:</strong> The Kanban board uses native HTML5 drag and drop &mdash; no plugins required. It works on both desktop and touch devices.
</div>

<h3>Column Counts</h3>
<p>Each column header shows the total number of leads in that status. This gives you an at-a-glance view of your pipeline health and helps identify bottlenecks (e.g., too many leads stuck in "' . (BusinessModeService::isRealEstate() ? 'Consultation' : 'Contacted') . '").</p>',
                    ],
                    [
                        'slug' => 'lead-import',
                        'title' => __('Importing Leads via CSV'),
                        'summary' => __('Bulk-import leads from spreadsheets with intelligent column mapping.'),
                        'tags' => ['import', 'CSV', 'bulk', 'spreadsheet', 'lists'],
                        'body' => '<h3>Overview</h3>
<p>Import thousands of leads at once from CSV files. This is ideal for ' . (BusinessModeService::isRealEstate() ? 'uploading IDX feeds, MLS exports, open house sign-in sheets, or migrating from another CRM' : 'uploading skip tracing results, purchased lead lists, or migrating from another CRM') . '.</p>

<h3>How to Import</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Go to <strong>Lists</strong> in the sidebar and click <strong>Import</strong>.</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Upload your CSV file. The system reads the column headers from the first row.</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content"><strong>Map columns</strong> &mdash; Match your CSV columns to InsulaCRM fields (first name, last name, phone, email, address, etc.). If AI is enabled, click <strong>AI Suggest Mapping</strong> to auto-detect column mappings.</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">Choose a <strong>list name</strong> to group the imported leads. Optionally set a default source and status.</div></div>
<div class="kb-step"><span class="kb-step-num">5</span><div class="kb-step-content">Click <strong>Import</strong>. The system processes the file, skips duplicates (based on phone or email), and checks against the DNC list.</div></div>

<h3>Import Results</h3>
<p>After import, you\'ll see a summary showing:</p>
<ul>
<li>Total rows processed</li>
<li>Leads imported successfully</li>
<li>Duplicates skipped</li>
<li>DNC matches flagged</li>
<li>Rows with errors</li>
</ul>

<h3>Managing Lists</h3>
<p>Imported leads are grouped into <strong>Lists</strong>. From the Lists page you can:</p>
<ul>
<li>View all leads in a specific list</li>
<li>See import history and stats</li>
<li>Delete a list (does not delete the leads themselves)</li>
</ul>

<div class="kb-callout">
<strong>CSV Format:</strong> The file must be UTF-8 encoded with comma separators. The first row must contain column headers. Maximum file size depends on your PHP configuration (typically 2-8 MB).
</div>',
                    ],
                    [
                        'slug' => 'lead-distribution',
                        'title' => __(BusinessModeService::isRealEstate() ? 'Lead Routing Methods' : 'Lead Distribution Methods'),
                        'summary' => __('Automatically assign new leads to your team using three strategies.'),
                        'tags' => ['distribution', 'routing', 'round robin', 'shark tank', 'hybrid', 'assignment'],
                        'body' => '<h3>Overview</h3>
<p>' . (BusinessModeService::isRealEstate() ? 'Lead routing' : 'Lead distribution') . ' determines how new leads are assigned to your team. Configure it in <strong>Settings &gt; ' . (BusinessModeService::isRealEstate() ? 'Lead Routing' : 'Distribution') . '</strong>.</p>

<h3>' . (BusinessModeService::isRealEstate() ? 'Routing Methods' : 'Distribution Methods') . '</h3>

<h4>Round Robin</h4>
<p>Leads are automatically assigned to active agents in rotation, ensuring perfectly even distribution. The system tracks whose turn it is and cycles through all eligible agents.</p>
<ul>
<li>Fairest distribution method</li>
<li>No manual intervention required</li>
<li>Skips inactive/deactivated agents</li>
</ul>

<h4>Shark Tank</h4>
<p>New leads are broadcast to <strong>all agents</strong>. The first agent to click <strong>Claim</strong> on the lead wins it. Unclaimed leads stay in the pool for others to grab.</p>
<ul>
<li>Rewards fast-acting agents</li>
<li>Creates healthy competition</li>
<li>Agents see a "Claim" button on unclaimed leads</li>
</ul>

<h4>Hybrid (Recommended)</h4>
<p>Combines both approaches. New leads are broadcast (Shark Tank style). If no agent claims the lead within the <strong>claim window</strong> (configurable, default 3 minutes), the system auto-assigns it via Round Robin.</p>
<ul>
<li>Best of both worlds</li>
<li>Ensures no leads go unassigned</li>
<li>Configurable claim timeout</li>
</ul>

<h3>Timezone Routing</h3>
<p>When enabled, leads are routed to agents whose working timezone matches the lead\'s timezone (determined by the property zip code). This ensures ' . (BusinessModeService::isRealEstate() ? 'contacts are reached' : 'sellers are contacted') . ' during appropriate hours.</p>

<h3>Configuration Options</h3>
<table>
<thead><tr><th>Setting</th><th>Description</th></tr></thead>
<tbody>
<tr><td>' . (BusinessModeService::isRealEstate() ? 'Routing Method' : 'Distribution Method') . '</td><td>Round Robin, Shark Tank, or Hybrid</td></tr>
<tr><td>Claim Window</td><td>Minutes before unclaimed leads get auto-assigned (Hybrid only)</td></tr>
<tr><td>Timezone Routing</td><td>Enable/disable timezone-based routing</td></tr>
<tr><td>Eligible Roles</td><td>Which roles receive lead assignments</td></tr>
</tbody>
</table>',
                    ],
                    [
                        'slug' => 'lead-scoring',
                        'title' => __('Motivation Scoring'),
                        'summary' => __('How the motivation score works and how AI can enhance it.'),
                        'tags' => ['scoring', 'motivation', 'priority', 'AI'],
                        'body' => '<h3>What Is Motivation Scoring?</h3>
<p>Every lead has a <strong>motivation score</strong> from 0 to 100 that indicates how likely the ' . (BusinessModeService::isRealEstate() ? 'client is to move forward with a transaction' : 'seller is to accept a deal') . '. Higher scores mean higher motivation and should be prioritized.</p>

<h3>Score Factors</h3>
<p>The score is calculated from multiple signals:</p>
<table>
<thead><tr><th>Factor</th><th>Impact</th></tr></thead>
<tbody>
<tr><td>Property condition</td><td>' . (BusinessModeService::isRealEstate() ? 'Properties needing updates may indicate urgency' : 'Higher score for distressed properties') . '</td></tr>
<tr><td>' . (BusinessModeService::isRealEstate() ? 'Client urgency / timeline' : 'Seller urgency / timeline') . '</td><td>Tight timelines increase score</td></tr>
<tr><td>' . (BusinessModeService::isRealEstate() ? 'Transaction readiness signals' : 'Financial distress signals') . '</td><td>' . (BusinessModeService::isRealEstate() ? 'Pre-approval, listing agreement signed, etc.' : 'Pre-foreclosure, tax liens, divorce, etc.') . '</td></tr>
<tr><td>Number of activities logged</td><td>More engagement = higher score</td></tr>
<tr><td>Response to outreach</td><td>' . (BusinessModeService::isRealEstate() ? 'Clients who respond score higher' : 'Sellers who respond score higher') . '</td></tr>
<tr><td>Time on market</td><td>Longer listed = higher motivation</td></tr>
</tbody>
</table>

<h3>AI-Powered Scoring</h3>
<p>With AI enabled (<strong>Settings &gt; AI</strong>), you get enhanced scoring that analyzes:</p>
<ul>
<li>All activity notes and conversation transcripts</li>
<li>Property signals and comparables</li>
<li>Behavioral patterns and engagement level</li>
<li>Contextual factors from lead details</li>
</ul>
<p>Click <strong>AI Score</strong> on any lead\'s detail page to generate an AI-powered motivation assessment with an explanation of the scoring rationale.</p>

<h3>Using Scores Effectively</h3>
<ul>
<li><strong>70-100 (Hot)</strong> &mdash; Priority follow-up. These leads are most likely to close. Highlighted in the leads list.</li>
<li><strong>40-69 (Warm)</strong> &mdash; Nurture with regular follow-ups and sequences.</li>
<li><strong>0-39 (Cold)</strong> &mdash; Low motivation. Keep in drip sequences for long-term nurturing.</li>
</ul>',
                    ],
                    [
                        'slug' => 'bulk-actions',
                        'title' => __('Bulk Actions & DNC Management'),
                        'summary' => __('Perform bulk operations on leads and manage Do Not Contact lists.'),
                        'tags' => ['bulk', 'DNC', 'mass update', 'export', 'tags'],
                        'body' => '<h3>Bulk Actions</h3>
<p>From the leads list, select multiple leads using the checkboxes, then use the <strong>Bulk Actions</strong> dropdown:</p>
<ul>
<li><strong>Change Status</strong> &mdash; Set all selected leads to a specific status</li>
<li><strong>Assign To</strong> &mdash; Reassign all selected leads to a different agent</li>
<li><strong>Add Tags</strong> &mdash; Apply one or more tags to all selected leads</li>
<li><strong>Export</strong> &mdash; Download selected leads as a CSV file</li>
<li><strong>Delete</strong> &mdash; Permanently delete selected leads (with confirmation)</li>
</ul>

<h3>Lead Export</h3>
<p>Click the <strong>Export</strong> button at the top of the leads list to download all leads (or filtered results) as a CSV file. The export includes all lead fields, property details, and the current status.</p>

<h3>Tags</h3>
<p>Tags help you categorize and segment leads. Create tags in <strong>Tags</strong> (sidebar), then apply them from the lead detail page or via bulk actions. Examples: ' . (BusinessModeService::isRealEstate() ? '"Hot List", "Open House Lead", "Past Client", "Relocation"' : '"Hot List", "Driving for Dollars", "Absentee Owner", "Probate"') . '.</p>

<h3>DNC (Do Not Contact) Management</h3>
<p>Manage your DNC list in <strong>Settings &gt; DNC</strong>:</p>
<ul>
<li><strong>Add Individual</strong> &mdash; Enter a phone number to add to the DNC list</li>
<li><strong>Bulk Import</strong> &mdash; Upload a CSV of phone numbers</li>
<li><strong>Automatic Checking</strong> &mdash; When creating or editing leads, phone numbers are checked against the DNC list automatically</li>
<li><strong>Remove</strong> &mdash; Delete entries from the DNC list</li>
</ul>

<div class="kb-callout-warning kb-callout">
<strong>Legal Compliance:</strong> Maintaining an accurate DNC list is a legal requirement. Always honor do-not-contact requests immediately.
</div>',
                    ],
                ],
            ],

            // ─── PROPERTIES ──────────────────────────────────────────────
            [
                'name' => __('Properties'),
                'icon' => 'building',
                'articles' => [
                    [
                        'slug' => 'managing-properties',
                        'title' => __('Managing Properties'),
                        'summary' => __(BusinessModeService::isRealEstate() ? 'Property records, listing details, and property management.' : 'Property records, field scout submissions, and property details.'),
                        'tags' => BusinessModeService::isRealEstate() ? ['properties', 'listings', 'address', 'photos'] : ['properties', 'field scout', 'address', 'photos'],
                        'body' => '<h3>Overview</h3>
<p>Properties in InsulaCRM represent physical real estate assets. ' . (BusinessModeService::isRealEstate() ? 'They are linked to leads and track listing details, pricing, and transaction history.' : 'They can be linked to leads (seller properties) or submitted independently by field scouts who find properties while driving for dollars.') . '</p>

<h3>Property Fields</h3>
<table>
<thead><tr><th>Field</th><th>Description</th></tr></thead>
<tbody>
<tr><td>Address</td><td>Full street address, city, state, zip</td></tr>
<tr><td>Property Type</td><td>Single Family, Multi-Family, Land, Commercial, Townhouse, Condo, Mobile Home</td></tr>
<tr><td>Bedrooms / Bathrooms</td><td>Number of beds and baths</td></tr>
<tr><td>Square Footage</td><td>Total living area</td></tr>
<tr><td>Year Built</td><td>Construction year</td></tr>
<tr><td>Lot Size</td><td>Lot area in acres or sqft</td></tr>
<tr><td>Condition</td><td>Excellent, Good, Fair, Poor, Distressed</td></tr>
<tr><td>' . (BusinessModeService::isRealEstate() ? 'List Price / Estimated Value' : 'Estimated Value / ARV') . '</td><td>' . (BusinessModeService::isRealEstate() ? 'Listing price and estimated market value' : 'Current value and after-repair value') . '</td></tr>
<tr><td>Notes</td><td>Additional observations</td></tr>
</tbody>
</table>

' . (BusinessModeService::isRealEstate() ? '' : '<h3>Field Scout Submissions</h3>
<p>Field Scouts have a simplified property submission form on their dashboard. They can:</p>
<ul>
<li>Enter the property address</li>
<li>Select property type and condition</li>
<li>Add notes about what they observed</li>
<li>Upload photos from their phone</li>
</ul>
<p>Submitted properties appear in the Properties list for admins and agents to review and potentially convert into leads.</p>

') . '<h3>AI Property Description</h3>
<p>If AI is enabled, you can click <strong>Generate Description</strong> on any property to create a professional property description based on its attributes. Useful for marketing materials and ' . (BusinessModeService::isRealEstate() ? 'client' : 'buyer') . ' notifications.</p>',
                    ],
                ],
            ],

            // ─── DEALS & PIPELINE ────────────────────────────────────────
            [
                'name' => __('Deals & Pipeline'),
                'icon' => 'layout-kanban',
                'articles' => [
                    [
                        'slug' => 'pipeline-overview',
                        'title' => __('Using the Deal Pipeline'),
                        'summary' => __('Manage deals from qualification through closing with the Kanban pipeline.'),
                        'tags' => ['pipeline', 'deals', 'kanban', 'stages', 'closing'],
                        'body' => '<h3>Overview</h3>
<p>The <strong>Pipeline</strong> is a Kanban board showing your active deals organized by stage. Each deal represents a transaction in progress.</p>

' . (BusinessModeService::isRealEstate() ? '<h3>Deal Stages</h3>
<table>
<thead><tr><th>Stage</th><th>Description</th></tr></thead>
<tbody>
<tr><td><strong>Lead</strong></td><td>New inquiry or prospect</td></tr>
<tr><td><strong>Listing Agreement</strong></td><td>Listing agreement signed with client</td></tr>
<tr><td><strong>Active Listing</strong></td><td>Property listed on MLS and marketed</td></tr>
<tr><td><strong>Showing</strong></td><td>Property being shown to prospective buyers</td></tr>
<tr><td><strong>Offer Received</strong></td><td>One or more offers received on the listing</td></tr>
<tr><td><strong>Under Contract</strong></td><td>Offer accepted, contract signed</td></tr>
<tr><td><strong>Inspection</strong></td><td>Inspection period and contingencies</td></tr>
<tr><td><strong>Appraisal</strong></td><td>Lender appraisal in progress</td></tr>
<tr><td><strong>Closing</strong></td><td>Preparing for and executing the closing</td></tr>
<tr><td><strong>Closed Won</strong></td><td>Transaction completed successfully</td></tr>
<tr><td><strong>Closed Lost</strong></td><td>Deal fell through</td></tr>
</tbody>
</table>' : '<h3>Deal Stages</h3>
<table>
<thead><tr><th>Stage</th><th>Description</th></tr></thead>
<tbody>
<tr><td><strong>Lead Qualified</strong></td><td>Lead has been vetted and is a potential deal</td></tr>
<tr><td><strong>Initial Offer</strong></td><td>Preparing or presenting the first offer</td></tr>
<tr><td><strong>Negotiation</strong></td><td>Back-and-forth on terms and price</td></tr>
<tr><td><strong>Under Contract</strong></td><td>Offer accepted, contract signed</td></tr>
<tr><td><strong>Due Diligence</strong></td><td>Title search, inspections, verifications</td></tr>
<tr><td><strong>Closing</strong></td><td>Preparing for and executing the closing</td></tr>
<tr><td><strong>Closed Won</strong></td><td>Transaction completed successfully</td></tr>
<tr><td><strong>Closed Lost</strong></td><td>Deal fell through</td></tr>
</tbody>
</table>') . '

<h3>The Deal Card</h3>
<p>Each deal card on the pipeline shows:</p>
<ul>
<li>Property address</li>
<li>Deal value (contract amount)</li>
<li>Assigned agent</li>
' . (BusinessModeService::isRealEstate() ? '<li>Transaction deadline countdown (if set)</li>
<li>Number of matched clients</li>' : '<li>Due diligence deadline countdown (if set)</li>
<li>Number of matched buyers</li>') . '
</ul>

<h3>Moving Deals</h3>
<p><strong>Drag and drop</strong> deal cards between columns to change their stage. You can also click a card to open the detail panel and change the stage from there.</p>

<h3>Deal Detail Panel</h3>
<p>Click any deal card to open a comprehensive detail panel showing:</p>
<ul>
' . (BusinessModeService::isRealEstate() ? '<li><strong>Financial Summary</strong> &mdash; Contract price, commission breakdown, listing details</li>' : '<li><strong>Financial Summary</strong> &mdash; Contract price, ARV, repair estimates, expected profit</li>') . '
<li><strong>Property Details</strong> &mdash; Full property record with photos</li>
<li><strong>Documents</strong> &mdash; Upload and manage contracts, title reports, inspection reports, etc.</li>
' . (BusinessModeService::isRealEstate() ? '<li><strong>Client Matches</strong> &mdash; Auto-matched clients with notify buttons</li>' : '<li><strong>Buyer Matches</strong> &mdash; Auto-matched buyers with notify buttons</li>') . '
<li><strong>Activity Log</strong> &mdash; Timeline of all deal-related activities</li>
<li><strong>AI Analysis</strong> &mdash; Risk assessment, opportunity scoring, and recommendations (if AI enabled)</li>
</ul>

' . (BusinessModeService::isRealEstate() ? '<h3>Transaction Deadline Tracking</h3>
<p>Set transaction deadlines on any deal. The system:</p>' : '<h3>Due Diligence Tracking</h3>
<p>Set a due diligence deadline on any deal. The system:</p>') . '
<ul>
<li>Shows a countdown timer on the deal card</li>
<li>Sends alerts when the deadline is approaching (within 3 days)</li>
<li>Marks deals as <strong>urgent</strong> when the deadline is within 2 days</li>
<li>Runs daily checks via the <code>deals:check-due-diligence</code> scheduled command</li>
</ul>

<h3>Documents</h3>
<p>Upload documents from the deal detail panel. Common document types:</p>
<ul>
<li>Purchase agreements / contracts</li>
<li>Title reports and title insurance</li>
<li>Inspection reports</li>
<li>Closing statements (HUD-1)</li>
' . (BusinessModeService::isRealEstate() ? '<li>Listing agreements</li>' : '<li>Assignment agreements</li>') . '
</ul>
<p>Documents are stored using your configured storage driver and can be downloaded at any time.</p>

<h3>Export</h3>
<p>Export your pipeline data as CSV using the <strong>Export</strong> button at the top of the pipeline page.</p>',
                    ],
                    [
                        'slug' => 'buyer-matching',
                        'title' => __(BusinessModeService::isRealEstate() ? 'Client Database & Deal Matching' : 'Buyer Database & Deal Matching'),
                        'summary' => __(BusinessModeService::isRealEstate() ? 'Manage clients, auto-match them to listings, and send notifications.' : 'Manage buyers, auto-match them to deals, and send notifications.'),
                        'tags' => BusinessModeService::isRealEstate() ? ['clients', 'matching', 'notifications', 'import'] : ['buyers', 'matching', 'disposition', 'notifications', 'import'],
                        'body' => '<h3>' . (BusinessModeService::isRealEstate() ? 'The Client Database' : 'The Buyer Database') . '</h3>
<p>Access <strong>' . (BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers') . '</strong> from the sidebar' . (BusinessModeService::isRealEstate() ? '.' : ' (available to admins and disposition agents).') . ' Each ' . (BusinessModeService::isRealEstate() ? 'client' : 'buyer') . ' record includes:</p>
<ul>
<li><strong>Contact Info</strong> &mdash; Name, company, phone, email</li>
<li><strong>' . (BusinessModeService::isRealEstate() ? 'Preferences' : 'Buying Criteria') . '</strong> &mdash; Preferred property types, locations (cities/zip codes), price range (min/max), and any special requirements</li>
<li><strong>Status</strong> &mdash; Active, Inactive, VIP</li>
<li><strong>' . (BusinessModeService::isRealEstate() ? 'Transaction History' : 'Purchase History') . '</strong> &mdash; Track of past ' . (BusinessModeService::isRealEstate() ? 'transactions' : 'purchases') . '</li>
</ul>

<h3>Adding ' . (BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers') . '</h3>
<p>Click <strong>Add ' . (BusinessModeService::isRealEstate() ? 'Client' : 'Buyer') . '</strong> and fill in their details. The more specific the ' . (BusinessModeService::isRealEstate() ? 'preferences' : 'buying criteria') . ', the better the automatic matching will work.</p>

<h3>Importing ' . (BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers') . '</h3>
<p>Click <strong>Import</strong> on the ' . (BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers') . ' page to bulk-import from a CSV file. Map columns to ' . (BusinessModeService::isRealEstate() ? 'client' : 'buyer') . ' fields just like lead import.</p>

<h3>Automatic Matching</h3>
<p>' . (BusinessModeService::isRealEstate() ? 'When a listing is active, the system automatically matches it against your client database based on:' : 'When a deal reaches disposition stages, the system automatically matches it against your buyer database based on:') . '</p>
<ul>
<li><strong>Location</strong> &mdash; ' . (BusinessModeService::isRealEstate() ? 'Client\'s' : 'Buyer\'s') . ' preferred cities/zip codes vs. property location</li>
<li><strong>Property Type</strong> &mdash; ' . (BusinessModeService::isRealEstate() ? 'Client\'s' : 'Buyer\'s') . ' preferred types vs. actual property type</li>
<li><strong>Price Range</strong> &mdash; Deal price falls within ' . (BusinessModeService::isRealEstate() ? 'client\'s' : 'buyer\'s') . ' min/max budget</li>
</ul>
<p>Matched ' . (BusinessModeService::isRealEstate() ? 'clients' : 'buyers') . ' appear in the deal detail panel sorted by match relevance.</p>

<h3>Notifying ' . (BusinessModeService::isRealEstate() ? 'Clients' : 'Buyers') . '</h3>
<p>From the deal detail panel, click <strong>Notify</strong> next to any matched ' . (BusinessModeService::isRealEstate() ? 'client' : 'buyer') . ' to send them a ' . (BusinessModeService::isRealEstate() ? 'listing' : 'deal') . ' notification. If AI is enabled, click <strong>AI Draft Message</strong> to generate a personalized outreach email tailored to that ' . (BusinessModeService::isRealEstate() ? 'client\'s' : 'buyer\'s') . ' preferences.</p>

<h3>' . (BusinessModeService::isRealEstate() ? 'Client' : 'Buyer') . ' Export</h3>
<p>Export your entire ' . (BusinessModeService::isRealEstate() ? 'client' : 'buyer') . ' database as CSV using the <strong>Export</strong> button.</p>',
                    ],
                ],
            ],

            // ─── COMMUNICATION & AUTOMATION ──────────────────────────────
            [
                'name' => __('Communication & Automation'),
                'icon' => 'message-circle',
                'articles' => [
                    [
                        'slug' => 'activities',
                        'title' => __('Logging Activities'),
                        'summary' => __('Track calls, emails, meetings, and notes for every lead and deal.'),
                        'tags' => ['activities', 'calls', 'SMS', 'email', 'notes', 'meetings'],
                        'body' => '<h3>What Are Activities?</h3>
<p>Activities are the chronological record of all interactions with leads and deals. They form the communication history and feed into motivation scoring and AI analysis.</p>

<h3>Activity Types</h3>
<table>
<thead><tr><th>Type</th><th>Description</th><th>Use When</th></tr></thead>
<tbody>
<tr><td><strong>Call</strong></td><td>Phone call record</td><td>Logging inbound/outbound phone calls</td></tr>
<tr><td><strong>SMS</strong></td><td>Text message record</td><td>Logging text conversations</td></tr>
<tr><td><strong>Email</strong></td><td>Email correspondence</td><td>Logging sent/received emails</td></tr>
<tr><td><strong>Meeting</strong></td><td>In-person or virtual meeting</td><td>Logging appointments, property viewings</td></tr>
<tr><td><strong>Note</strong></td><td>Internal note</td><td>Recording observations, reminders, internal comments</td></tr>
<tr><td><strong>Site Visit</strong></td><td>Property inspection</td><td>Logging property visits, condition assessments</td></tr>
</tbody>
</table>

<h3>Logging an Activity</h3>
<p>From a lead or deal detail page:</p>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Click <strong>Log Activity</strong> in the activity section</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Select the activity type</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Enter a description of what happened</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">Optionally set the date/time (defaults to now)</div></div>
<div class="kb-step"><span class="kb-step-num">5</span><div class="kb-step-content">Click <strong>Save</strong></div></div>

<h3>AI-Powered Follow-Ups</h3>
<p>With AI enabled, you get smart follow-up tools on every lead:</p>
<ul>
<li><strong>Draft Follow-Up</strong> &mdash; AI analyzes all activities and generates a personalized follow-up script (SMS, email, or voicemail)</li>
<li><strong>Summarize Notes</strong> &mdash; AI reads all activities and produces a concise summary of the lead\'s situation</li>
<li><strong>Objection Responses</strong> &mdash; AI suggests responses to common ' . (BusinessModeService::isRealEstate() ? 'client objections' : 'seller objections') . '</li>
</ul>',
                    ],
                    [
                        'slug' => 'sequences',
                        'title' => __('Drip Sequences'),
                        'summary' => __('Automate multi-step follow-up campaigns that run on autopilot.'),
                        'tags' => ['sequences', 'drip', 'automation', 'follow-up', 'campaigns'],
                        'body' => '<h3>What Are Sequences?</h3>
<p>Sequences are automated multi-step follow-up campaigns. Once a lead is enrolled, the system executes each step automatically on schedule &mdash; so you never forget to follow up.</p>

<h3>Creating a Sequence</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Go to <strong>Sequences</strong> in the sidebar and click <strong>Create Sequence</strong></div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Give it a name (e.g., "New Lead 7-Day Follow-Up")</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Add steps. Each step has:
    <ul>
        <li><strong>Type</strong> &mdash; SMS, Email, Call, or Task (reminder to call)</li>
        <li><strong>Delay</strong> &mdash; Days to wait after the previous step (e.g., 1, 3, 7)</li>
        <li><strong>Content</strong> &mdash; The message template or script to use</li>
    </ul>
</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">If AI is enabled, click <strong>AI Generate Steps</strong> to auto-create a complete sequence with optimized timing and personalized scripts.</div></div>

<h4>Example Sequence</h4>
' . (BusinessModeService::isRealEstate() ? '<table>
<thead><tr><th>Step</th><th>Day</th><th>Type</th><th>Content</th></tr></thead>
<tbody>
<tr><td>1</td><td>0</td><td>SMS</td><td>"Hi {name}, thank you for your inquiry about {address}. I\'d love to schedule a time to chat."</td></tr>
<tr><td>2</td><td>2</td><td>Call</td><td>Follow-up call to discuss needs and timeline</td></tr>
<tr><td>3</td><td>5</td><td>Email</td><td>Market update email with comparable listings</td></tr>
<tr><td>4</td><td>10</td><td>SMS</td><td>"Just checking in &mdash; would you like to schedule a consultation?"</td></tr>
<tr><td>5</td><td>21</td><td>Email</td><td>Final follow-up with recent success stories</td></tr>
</tbody>
</table>' : '<table>
<thead><tr><th>Step</th><th>Day</th><th>Type</th><th>Content</th></tr></thead>
<tbody>
<tr><td>1</td><td>0</td><td>SMS</td><td>"Hi {name}, I saw your property at {address}. Are you still interested in selling?"</td></tr>
<tr><td>2</td><td>2</td><td>Call</td><td>Follow-up call script</td></tr>
<tr><td>3</td><td>5</td><td>Email</td><td>Value proposition email with comparable sales</td></tr>
<tr><td>4</td><td>10</td><td>SMS</td><td>"Just checking in &mdash; would you like to discuss an offer?"</td></tr>
<tr><td>5</td><td>21</td><td>Email</td><td>Final follow-up with testimonials</td></tr>
</tbody>
</table>') . '

<h3>Enrolling Leads</h3>
<p>From any lead\'s detail page, click <strong>Enroll in Sequence</strong> and select which sequence to use. The first step executes immediately (or on Day 0), then subsequent steps follow the defined delays.</p>

<h3>How Processing Works</h3>
<p>The scheduled command <code>sequences:process</code> runs daily and:</p>
<ul>
<li>Checks all active enrollments for pending steps</li>
<li>Executes steps whose delay has elapsed</li>
<li>Logs the action as an activity on the lead</li>
<li>Advances to the next step</li>
</ul>

<h3>Automatic Unenrollment</h3>
<p>Leads are automatically removed from sequences when:</p>
<ul>
<li>They complete all steps</li>
<li>Their status changes to Closed Won, Closed Lost, or DNC</li>
<li>They are manually unenrolled by an agent</li>
</ul>',
                    ],
                    [
                        'slug' => 'email-templates',
                        'title' => __('Email Templates'),
                        'summary' => __('Create reusable email templates for outreach and notifications.'),
                        'tags' => ['email', 'templates', 'outreach', 'SMTP'],
                        'body' => '<h3>Managing Templates</h3>
<p>Go to <strong>Settings &gt; Email &gt; Manage Email Templates</strong> to create and manage reusable email templates.</p>

<h3>Creating a Template</h3>
<p>Each template has:</p>
<ul>
<li><strong>Name</strong> &mdash; Internal identifier (e.g., "Initial Outreach", "Under Contract Notification")</li>
<li><strong>Subject Line</strong> &mdash; The email subject</li>
<li><strong>Body</strong> &mdash; HTML email content</li>
</ul>

<h3>Using Templates</h3>
<p>Templates can be used in:</p>
<ul>
<li>Sequence email steps</li>
<li>Manual outreach from lead detail pages</li>
<li>' . (BusinessModeService::isRealEstate() ? 'Client notification emails' : 'Buyer notification emails') . '</li>
</ul>

<h3>Preview</h3>
<p>Click <strong>Preview</strong> to see how the template will render before sending. This shows the formatted HTML as the recipient would see it.</p>

<h3>SMTP Configuration</h3>
<p>Before sending emails, configure your SMTP settings in <strong>Settings &gt; Email</strong>:</p>
<table>
<thead><tr><th>Field</th><th>Description</th></tr></thead>
<tbody>
<tr><td>SMTP Host</td><td>e.g., smtp.gmail.com, smtp.office365.com</td></tr>
<tr><td>SMTP Port</td><td>587 (TLS) or 465 (SSL)</td></tr>
<tr><td>Username</td><td>Your email address</td></tr>
<tr><td>Password</td><td>App password or SMTP password</td></tr>
<tr><td>Encryption</td><td>TLS (recommended) or SSL</td></tr>
<tr><td>From Address</td><td>The "from" email for outgoing messages</td></tr>
<tr><td>From Name</td><td>Display name for outgoing messages</td></tr>
</tbody>
</table>
<p>Click <strong>Send Test Email</strong> after configuring to verify your settings work.</p>',
                    ],
                ],
            ],

            // ─── CALENDAR & TASKS ────────────────────────────────────────
            [
                'name' => __('Calendar & Tasks'),
                'icon' => 'calendar',
                'articles' => [
                    [
                        'slug' => 'tasks',
                        'title' => __('Managing Tasks'),
                        'summary' => __('Create tasks linked to leads, mark them complete, and stay organized.'),
                        'tags' => ['tasks', 'to-do', 'reminders', 'organization'],
                        'body' => '<h3>Creating Tasks</h3>
<p>Tasks are linked to leads and help you track follow-up actions. Create tasks from the lead detail page:</p>
<ul>
<li><strong>Title</strong> &mdash; What needs to be done (e.g., "Call back Tuesday", "Send contract")</li>
<li><strong>Due Date</strong> &mdash; When the task should be completed</li>
<li><strong>Assigned To</strong> &mdash; Which team member is responsible</li>
</ul>

<h3>Task Management</h3>
<ul>
<li><strong>Toggle Complete</strong> &mdash; Click the checkbox to mark a task as done</li>
<li><strong>Delete</strong> &mdash; Remove tasks you no longer need</li>
<li><strong>Calendar View</strong> &mdash; See all tasks on the calendar page</li>
</ul>

<h3>AI Task Suggestions</h3>
<p>With AI enabled, click <strong>Suggest Tasks</strong> on any lead to get AI-recommended next actions based on the lead\'s current status, activity history, and engagement level.</p>

<h3>Upcoming Tasks Widget</h3>
<p>The dashboard shows an <strong>Upcoming Tasks</strong> widget with your tasks due in the next 7 days, helping you prioritize your day.</p>',
                    ],
                    [
                        'slug' => 'calendar-sync',
                        'title' => __('Calendar Sync with Google & Outlook'),
                        'summary' => __('Export your CRM calendar to Google Calendar, Outlook, or Apple Calendar.'),
                        'tags' => ['calendar', 'sync', 'Google', 'Outlook', 'iCal', 'Apple'],
                        'body' => '<h3>Overview</h3>
<p>Sync your InsulaCRM tasks and activities with external calendar apps using the standard iCal feed format.</p>

<h3>Generating Your Feed URL</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Go to <strong>Calendar</strong> and click the <strong>Sync</strong> button</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Click <strong>Generate Feed URL</strong> to create your private iCal feed</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Copy the feed URL (it contains a unique token &mdash; don\'t share it)</div></div>

<h3>Google Calendar</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Open Google Calendar</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Click the <strong>+</strong> next to "Other calendars" on the left sidebar</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Select <strong>From URL</strong></div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">Paste your feed URL and click <strong>Add calendar</strong></div></div>

<h3>Microsoft Outlook</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Open Outlook Calendar</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Click <strong>Add calendar</strong> &rarr; <strong>Subscribe from web</strong></div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Paste your feed URL and click <strong>Import</strong></div></div>

<h3>Apple Calendar (macOS/iOS)</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Open Calendar app</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">File &rarr; <strong>New Calendar Subscription</strong></div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Paste your feed URL and configure refresh interval</div></div>

<h3>Importing External Calendars</h3>
<p>You can also import events from an external calendar into InsulaCRM. On the Sync settings page, enter an external iCal URL and click <strong>Import</strong>. Each event is created as a task in your CRM.</p>

<h3>Disconnecting</h3>
<p>Click <strong>Disconnect</strong> on the Sync settings page to revoke your feed URL. External calendar apps will no longer receive updates. You can generate a new URL at any time.</p>

<div class="kb-callout">
<strong>Note:</strong> External calendar apps typically refresh subscribed feeds every 12-24 hours. Changes may not appear immediately.
</div>',
                    ],
                ],
            ],

            // ─── REPORTS & ANALYTICS ─────────────────────────────────────
            [
                'name' => __('Reports & Analytics'),
                'icon' => 'chart-bar',
                'articles' => [
                    [
                        'slug' => 'reports',
                        'title' => __('Reports Overview'),
                        'summary' => __('Access conversion funnels, pipeline analytics, team performance, and ROI reports.'),
                        'tags' => ['reports', 'analytics', 'dashboard', 'KPI'],
                        'body' => '<h3>Accessing Reports</h3>
<p>Navigate to <strong>Reports</strong> in the sidebar (admin only). The reports page provides comprehensive analytics across your business.</p>

<h3>Available Reports</h3>

<h4>Conversion Funnel</h4>
<p>Visualizes how leads progress through each status, from New to Closed Won. Shows:</p>
<ul>
<li>Number of leads at each stage</li>
<li>Conversion rate between stages</li>
<li>Drop-off points where leads are lost</li>
<li>Exportable as CSV</li>
</ul>

<h4>Pipeline Bottleneck</h4>
<p>Identifies which ' . (BusinessModeService::isRealEstate() ? 'transaction stages have the most active transactions' : 'deal stages have the most active deals') . ' and longest average duration. Stages are rated:</p>
<ul>
<li><strong>Healthy</strong> &mdash; Normal throughput</li>
<li><strong>Slow</strong> &mdash; Deals spending too long in this stage</li>
<li><strong>Critical</strong> &mdash; Significant bottleneck requiring attention</li>
</ul>

<h4>Agent Performance</h4>
<p>Compare team members side-by-side on metrics including:</p>
<ul>
<li>Leads handled and conversion rates</li>
<li>Activities logged (calls, emails, meetings)</li>
<li>' . (BusinessModeService::isRealEstate() ? 'Transactions closed and commission earned' : 'Deals closed and revenue generated') . '</li>
<li>Average response time</li>
</ul>

<h4>Lead Source ROI</h4>
<p>Track the return on investment for each marketing channel. Requires setting monthly budgets for each lead source in <strong>Settings &gt; Lead Source Costs</strong>. Shows:</p>
<ul>
<li>Cost per lead by source</li>
<li>Conversion rate by source</li>
<li>Revenue generated by source</li>
<li>ROI percentage</li>
</ul>

<h4>List Stacking</h4>
<p>Analyzes overlap between your imported lead lists. Leads appearing on multiple lists are often higher-priority ' . (BusinessModeService::isRealEstate() ? 'prospects' : 'motivated sellers') . '.</p>

<h3>Dashboard Charts</h3>
<p>The main dashboard also shows chart widgets (powered by ApexCharts) that load independently via AJAX:</p>
<ul>
<li>KPI summary cards (total leads, active ' . (BusinessModeService::isRealEstate() ? 'transactions, conversion rate, commission' : 'deals, conversion rate, revenue') . ')</li>
<li>Recent leads activity</li>
<li>Pipeline overview chart</li>
<li>Team leaderboard</li>
<li>Lead source ROI summary</li>
</ul>

<h3>Exporting Reports</h3>
<ul>
<li><strong>PDF Export</strong> &mdash; Click the PDF buttons at the top of the reports page for printable Lead, Pipeline, or Team reports with the current date range</li>
<li><strong>CSV Export</strong> &mdash; Export raw data for individual reports (funnel, agents, sources, list stacking)</li>
</ul>',
                    ],
                ],
            ],

            // ─── SETTINGS & CONFIGURATION ────────────────────────────────
            [
                'name' => __('Settings & Configuration'),
                'icon' => 'settings',
                'articles' => [
                    [
                        'slug' => 'general-settings',
                        'title' => __('General Settings'),
                        'summary' => __('Configure company name, timezone, currency, date format, and branding.'),
                        'tags' => ['settings', 'company', 'timezone', 'currency', 'logo', 'branding'],
                        'body' => '<h3>Accessing Settings</h3>
<p>Navigate to <strong>Settings</strong> in the sidebar (admin only). Settings are organized into tabs across the top of the page.</p>

<h3>General Tab</h3>
<table>
<thead><tr><th>Setting</th><th>Description</th></tr></thead>
<tbody>
<tr><td>Company Name</td><td>Displayed in the sidebar, emails, and exported documents</td></tr>
<tr><td>Timezone</td><td>Used for date display, scheduling, and timezone routing</td></tr>
<tr><td>Currency</td><td>Display currency for monetary values (USD, EUR, GBP, etc.)</td></tr>
<tr><td>Date Format</td><td>How dates are displayed throughout the CRM</td></tr>
<tr><td>Company Logo</td><td>Upload a logo to replace the default InsulaCRM logo in the sidebar</td></tr>
<tr><td>Default Lead Status</td><td>Status assigned to new leads (default: "New")</td></tr>
</tbody>
</table>

<h3>Custom Fields</h3>
<p>Manage dropdown options used throughout the CRM in <strong>Settings &gt; Custom Fields</strong>:</p>
<ul>
<li><strong>Lead Sources</strong> &mdash; Add/remove marketing channels (' . (BusinessModeService::isRealEstate() ? 'Open House, Zillow, MLS, Referral, etc.' : 'Direct Mail, PPC, Cold Call, Driving for Dollars, etc.') . ')</li>
<li><strong>Lead Statuses</strong> &mdash; Default statuses are built-in but you can customize as needed</li>
<li><strong>Property Types</strong> &mdash; Add custom property type options</li>
</ul>

<h3>Branding</h3>
<p>Upload your company logo to customize the sidebar. The logo appears for all team members. Recommended size: 330px wide, transparent background PNG for best results with both light and dark themes.</p>',
                    ],
                    [
                        'slug' => 'team-management',
                        'title' => __('Team Management'),
                        'summary' => __('Invite agents, manage user accounts, reset 2FA, and impersonate users.'),
                        'tags' => ['team', 'invite', 'agents', 'users', '2FA', 'impersonate'],
                        'body' => '<h3>Inviting Team Members</h3>
<p>Go to <strong>Settings &gt; Team</strong> and use the team form at the top of the page:</p>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Enter the person\'s <strong>name</strong> and <strong>email address</strong></div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Select their <strong>role</strong> (' . (BusinessModeService::isRealEstate() ? 'Admin, Agent, Listing Agent, or Buyers Agent' : 'Admin, Agent, Acquisition Agent, Disposition Agent, or Field Scout') . ')</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Set an <strong>initial password</strong> for the new user</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">Click <strong>Add</strong> to create the account</div></div>

<h3>Managing Existing Users</h3>
<p>The Team tab shows all users with their:</p>
<ul>
<li>Name and email</li>
<li>Role</li>
<li>Active/inactive status</li>
<li>2FA status</li>
<li>Last login date</li>
</ul>

<h3>Available Actions</h3>
<table>
<thead><tr><th>Action</th><th>Description</th></tr></thead>
<tbody>
<tr><td><strong>Toggle Active</strong></td><td>Activate/deactivate a user. Inactive users cannot log in.</td></tr>
<tr><td><strong>Reset 2FA</strong></td><td>Clear a user\'s two-factor authentication if they lost their authenticator device.</td></tr>
<tr><td><strong>Impersonate</strong></td><td>Log in as this user to see the CRM from their perspective. Useful for troubleshooting.</td></tr>
</tbody>
</table>

<h3>Two-Factor Authentication (2FA)</h3>
<p>Users can enable 2FA from their <strong>Profile</strong> page using any TOTP authenticator app (Google Authenticator, Authy, etc.). Admins can optionally require 2FA for all users. If a user loses their device, an admin can reset their 2FA from the Team settings.</p>',
                    ],
                    [
                        'slug' => 'api-setup',
                        'title' => __('API & Webhooks'),
                        'summary' => __('Set up your API key, web forms, and webhook event notifications.'),
                        'tags' => ['API', 'webhooks', 'Zapier', 'integration', 'forms', 'REST'],
                        'body' => '<h3>API Key</h3>
<p>Generate your API key in <strong>Settings &gt; API</strong>:</p>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Click <strong>Generate API Key</strong></div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Copy and securely store the key &mdash; it\'s shown only once</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Use the <strong>Enable/Disable</strong> toggle to control API access</div></div>

<h3>REST API Endpoints</h3>
<p>With an active API key, you can push leads via HTTP POST. The full API reference is available at <strong>API Docs</strong> in the sidebar, including:</p>
<ul>
<li>Lead creation and retrieval</li>
<li>Lead status updates</li>
<li>' . (BusinessModeService::isRealEstate() ? 'Client creation' : 'Buyer creation') . '</li>
<li>' . (BusinessModeService::isRealEstate() ? 'Transaction stage updates' : 'Deal stage updates') . '</li>
<li>Activity logging</li>
<li>OpenAPI/Swagger spec download</li>
</ul>
<p>All API requests require the header: <code>X-API-Key: your-api-key</code></p>

<h3>Web Forms</h3>
<p>Once your API is enabled, you get an embeddable <strong>web form</strong>:</p>
<ul>
<li><strong>Direct Link</strong> &mdash; Share the URL for ' . (BusinessModeService::isRealEstate() ? 'prospects to fill out directly' : 'sellers to fill out directly') . '</li>
<li><strong>Embed Code</strong> &mdash; Copy the iframe HTML to embed on your website</li>
<li>Submissions automatically create leads with the configured lead source</li>
</ul>

<h3>Webhooks</h3>
<p>Configure webhook endpoints in <strong>Settings &gt; Webhooks</strong> to receive real-time HTTP POST notifications:</p>
<table>
<thead><tr><th>Event</th><th>Fires When</th></tr></thead>
<tbody>
<tr><td>lead.created</td><td>A new lead is added</td></tr>
<tr><td>lead.updated</td><td>Lead details are modified</td></tr>
<tr><td>lead.status_changed</td><td>Lead status changes</td></tr>
<tr><td>deal.stage_changed</td><td>Deal moves to a new pipeline stage</td></tr>
<tr><td>activity.logged</td><td>An activity is recorded</td></tr>
<tr><td>buyer.notified</td><td>' . (BusinessModeService::isRealEstate() ? 'A client is notified about a listing' : 'A buyer is notified about a deal') . '</td></tr>
<tr><td>sequence.step_executed</td><td>A sequence step is processed</td></tr>
</tbody>
</table>
<p>Webhooks support <strong>HMAC signing</strong> for payload verification. Each webhook can be individually enabled/disabled.</p>

<h3>Integration with Zapier</h3>
<p>Use the REST API with Zapier\'s Webhooks integration to connect InsulaCRM with 5,000+ apps. Common integrations:</p>
<ul>
<li>New lead from Facebook Lead Ads &rarr; Create lead in InsulaCRM</li>
<li>New lead from Google Forms &rarr; Create lead in InsulaCRM</li>
<li>Lead status changes in InsulaCRM &rarr; Send Slack notification</li>
</ul>',
                    ],
                    [
                        'slug' => 'ai-setup',
                        'title' => __('AI Assistant Setup'),
                        'summary' => __('Connect OpenAI, Claude, Gemini, or Ollama to unlock smart CRM features.'),
                        'tags' => ['AI', 'OpenAI', 'Claude', 'Gemini', 'Ollama', 'machine learning'],
                        'body' => '<h3>Supported Providers</h3>
<table>
<thead><tr><th>Provider</th><th>API Key Required</th><th>Notes</th></tr></thead>
<tbody>
<tr><td><strong>OpenAI</strong></td><td>Yes</td><td>GPT-4o, GPT-4, GPT-3.5-Turbo</td></tr>
<tr><td><strong>Anthropic Claude</strong></td><td>Yes</td><td>Claude Sonnet 4, Claude Haiku, etc.</td></tr>
<tr><td><strong>Google Gemini</strong></td><td>Yes</td><td>Gemini Pro, Gemini Flash</td></tr>
<tr><td><strong>Ollama</strong></td><td>No</td><td>Self-hosted local models (Llama, Mistral, etc.)</td></tr>
<tr><td><strong>Custom OpenAI-Compatible</strong></td><td>Varies</td><td>Any server with an OpenAI-compatible API (LM Studio, vLLM, etc.)</td></tr>
</tbody>
</table>

<h3>Setup Steps</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Go to <strong>Settings &gt; AI</strong></div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Select your <strong>provider</strong> from the dropdown</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Enter your <strong>API key</strong> (not needed for Ollama)</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">Optionally enter a <strong>custom endpoint URL</strong> (for Ollama or custom servers)</div></div>
<div class="kb-step"><span class="kb-step-num">5</span><div class="kb-step-content">Choose a <strong>model</strong> or click <strong>List Models</strong> to see available models from your provider</div></div>
<div class="kb-step"><span class="kb-step-num">6</span><div class="kb-step-content">Click <strong>Test Connection</strong> to verify everything works</div></div>
<div class="kb-step"><span class="kb-step-num">7</span><div class="kb-step-content">Click <strong>Save</strong> then toggle <strong>Enable AI</strong></div></div>

<h3>AI Features Reference</h3>
<table>
<thead><tr><th>Feature</th><th>Location</th><th>Description</th></tr></thead>
<tbody>
<tr><td>Draft Follow-Up</td><td>Lead detail</td><td>Generate personalized SMS, email, or voicemail scripts</td></tr>
<tr><td>Summarize Notes</td><td>Lead detail</td><td>AI summary of all activities and notes</td></tr>
<tr><td>AI Lead Scoring</td><td>Lead detail</td><td>Motivation scoring with reasoning</td></tr>
<tr><td>Objection Responses</td><td>Lead detail</td><td>Suggested responses to ' . (BusinessModeService::isRealEstate() ? 'client' : 'seller') . ' objections</td></tr>
<tr><td>Suggest Tasks</td><td>Lead detail</td><td>AI-recommended next actions</td></tr>
<tr><td>' . (BusinessModeService::isRealEstate() ? 'Analyze Transaction' : 'Analyze Deal') . '</td><td>Pipeline ' . (BusinessModeService::isRealEstate() ? 'transaction' : 'deal') . ' panel</td><td>Risk assessment, opportunity scoring, recommendations</td></tr>
<tr><td>' . (BusinessModeService::isRealEstate() ? 'Stage Advice' : 'Deal Stage Advice') . '</td><td>Pipeline ' . (BusinessModeService::isRealEstate() ? 'transaction' : 'deal') . ' panel</td><td>Guidance for the current ' . (BusinessModeService::isRealEstate() ? 'transaction' : 'deal') . ' stage</td></tr>
<tr><td>Draft ' . (BusinessModeService::isRealEstate() ? 'Client' : 'Buyer') . ' Message</td><td>Pipeline ' . (BusinessModeService::isRealEstate() ? 'transaction' : 'deal') . ' panel</td><td>Personalized ' . (BusinessModeService::isRealEstate() ? 'client' : 'buyer') . ' outreach emails</td></tr>
<tr><td>Explain ' . (BusinessModeService::isRealEstate() ? 'Client' : 'Buyer') . ' Match</td><td>Pipeline ' . (BusinessModeService::isRealEstate() ? 'transaction' : 'deal') . ' panel</td><td>Why a specific ' . (BusinessModeService::isRealEstate() ? 'client' : 'buyer') . ' is a good match</td></tr>
<tr><td>Offer Strategy</td><td>Lead/' . (BusinessModeService::isRealEstate() ? 'Transaction' : 'Deal') . '</td><td>AI-generated offer strategy and pricing</td></tr>
<tr><td>Property Description</td><td>Property detail</td><td>Professional property marketing copy</td></tr>
<tr><td>CSV Mapping</td><td>List import</td><td>Auto-detect column mappings for imports</td></tr>
<tr><td>Generate Sequence</td><td>Sequence builder</td><td>Auto-create all sequence steps with content</td></tr>
<tr><td>Weekly Digest</td><td>Dashboard</td><td>AI summary of your week\'s performance</td></tr>
<tr><td>DNC Risk Check</td><td>Lead detail</td><td>Flag potential compliance risks</td></tr>
</tbody>
</table>

<div class="kb-callout">
<strong>Privacy:</strong> AI requests send only the necessary data (lead notes, property details) to the AI provider. No data is stored by the AI provider beyond processing the request. Sensitive fields (SSN, bank details) are never sent.
</div>',
                    ],
                    [
                        'slug' => 'security-sso',
                        'title' => __('Security & Single Sign-On'),
                        'summary' => __('Configure 2FA enforcement, SSO providers, and related access controls.'),
                        'tags' => ['security', 'SSO', '2FA', 'Google login', 'password'],
                        'body' => '<h3>Two-Factor Authentication</h3>
<p>InsulaCRM supports TOTP-based two-factor authentication. Users can enable 2FA from their <strong>Profile</strong> page:</p>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Go to <strong>Profile &gt; Two-Factor Authentication</strong></div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Scan the QR code with your authenticator app (Google Authenticator, Authy, 1Password, etc.)</div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Enter the 6-digit code to confirm and activate 2FA</div></div>

<h3>Security Settings</h3>
<p>Admins can configure authentication and integration security in <strong>Settings &gt; Integrations</strong>:</p>
<table>
<thead><tr><th>Setting</th><th>Description</th></tr></thead>
<tbody>
<tr><td>Require 2FA</td><td>Force all users to set up 2FA before accessing the CRM</td></tr>
<tr><td>SSO Providers</td><td>Configure plugin-based SSO providers that implement the CRM\'s SSO contracts</td></tr>
<tr><td>Plugin Providers</td><td>Add custom 2FA, SSO, or SMS providers through plugins and integrations</td></tr>
</tbody>
</table>

<h3>Single Sign-On (SSO)</h3>
<p>InsulaCRM includes an SSO framework, but SSO providers are added through plugins or custom integrations. That means there are no built-in Google, Microsoft, or Okta sign-in providers in the base package.</p>
<p>Once an SSO plugin is installed, users can sign in with the provider configured for your tenant. Typical examples include:</p>
<ul>
<li><strong>Google</strong> &mdash; Sign in with Google Workspace or personal Gmail</li>
<li><strong>Microsoft</strong> &mdash; Sign in with Microsoft 365 / Azure AD</li>
<li><strong>Okta</strong> or other identity providers &mdash; via plugin-based SSO connectors</li>
</ul>
<p>Configure SSO in <strong>Settings &gt; Integrations</strong> after installing the relevant provider plugin. You\'ll need the provider\'s client credentials and callback configuration required by that plugin.</p>

<h3>Security Headers</h3>
<p>InsulaCRM automatically sends security headers on every response:</p>
<ul>
<li><code>X-Content-Type-Options: nosniff</code></li>
<li><code>X-Frame-Options: SAMEORIGIN</code></li>
<li><code>X-XSS-Protection: 1; mode=block</code></li>
<li><code>Referrer-Policy: strict-origin-when-cross-origin</code></li>
</ul>',
                    ],
                    [
                        'slug' => 'storage',
                        'title' => __('File Storage Configuration'),
                        'summary' => __('Configure local or S3-compatible cloud storage for files and photos.'),
                        'tags' => ['storage', 'S3', 'cloud', 'files', 'uploads', 'DigitalOcean', 'MinIO'],
                        'body' => '<h3>Storage Drivers</h3>
<p>Configure file storage in <strong>Settings &gt; Storage</strong>. InsulaCRM supports two storage drivers:</p>

<h4>Local Storage (Default)</h4>
<p>Files are stored on the server filesystem in the <code>storage/app/public</code> directory. This is the simplest option and requires no additional configuration.</p>
<ul>
<li>No external service needed</li>
<li>Limited by server disk space</li>
<li>Files are served directly by your web server</li>
</ul>

<h4>Amazon S3 / S3-Compatible</h4>
<p>Store files in the cloud for scalability, redundancy, and CDN capabilities. Compatible with:</p>
<ul>
<li><strong>Amazon S3</strong> &mdash; AWS\'s object storage</li>
<li><strong>DigitalOcean Spaces</strong> &mdash; S3-compatible cloud storage</li>
<li><strong>MinIO</strong> &mdash; Self-hosted S3-compatible storage</li>
<li><strong>Wasabi</strong> &mdash; Hot cloud storage</li>
<li><strong>Backblaze B2</strong> &mdash; S3-compatible mode</li>
</ul>

<h3>S3 Configuration</h3>
<table>
<thead><tr><th>Field</th><th>Description</th><th>Example</th></tr></thead>
<tbody>
<tr><td>Access Key ID</td><td>Your S3 access key</td><td><code>AKIAIOSFODNN7EXAMPLE</code></td></tr>
<tr><td>Secret Access Key</td><td>Your S3 secret (encrypted before storage)</td><td><code>wJalrXUtnFEMI/K7MDENG/...</code></td></tr>
<tr><td>Region</td><td>S3 region</td><td><code>us-east-1</code>, <code>eu-west-1</code></td></tr>
<tr><td>Bucket Name</td><td>The S3 bucket to use</td><td><code>my-crm-files</code></td></tr>
<tr><td>Endpoint URL</td><td>Custom endpoint (for non-AWS services)</td><td><code>https://nyc3.digitaloceanspaces.com</code></td></tr>
</tbody>
</table>

<div class="kb-callout">
<strong>Security:</strong> Your Secret Access Key is encrypted using Laravel\'s encryption before being stored in the database. It is never exposed in plain text.
</div>

<h3>After Switching</h3>
<p>When switching from Local to S3 (or vice versa), existing files remain in the original location. New uploads will use the new driver. To migrate existing files, you would need to manually copy them to the new storage location.</p>',
                    ],
                    [
                        'slug' => 'backups',
                        'title' => __('Database Backups'),
                        'summary' => __('Create, download, and manage database backups from the UI.'),
                        'tags' => ['backup', 'restore', 'database', 'safety', 'disaster recovery'],
                        'body' => '<h3>Overview</h3>
<p>Regular backups protect your data against accidental deletion, corruption, or server failures. Manage backups in <strong>Settings &gt; Backups</strong>.</p>

<h3>Creating a Backup</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Go to <strong>Settings &gt; Backups</strong></div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Click <strong>Create Backup Now</strong></div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Wait for the process to complete (duration depends on database size)</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">The new backup appears in the list with its filename, size, and creation date</div></div>

<h3>Managing Backups</h3>
<table>
<thead><tr><th>Action</th><th>Description</th></tr></thead>
<tbody>
<tr><td><strong>Download</strong></td><td>Download the backup file to your local computer for offsite storage</td></tr>
<tr><td><strong>Delete</strong></td><td>Remove a backup file from the server to free up disk space</td></tr>
</tbody>
</table>

<h3>Automatic Cleanup</h3>
<p>Old backups are automatically cleaned up daily at 1:00 AM via the <code>backup:clean</code> scheduled command. This prevents the backup directory from growing indefinitely.</p>

<h3>Best Practices</h3>
<ul>
<li><strong>Regular backups</strong> &mdash; Create backups before making major changes (bulk imports, plugin installations, updates)</li>
<li><strong>Offsite copies</strong> &mdash; Always download backups and store them somewhere other than the server (cloud storage, external drive, another server)</li>
<li><strong>Test restores</strong> &mdash; Periodically verify that your backups can be restored successfully</li>
<li><strong>Before updates</strong> &mdash; Always backup before updating InsulaCRM</li>
<li><strong>Before risky changes</strong> &mdash; Use a manual recovery snapshot in <strong>Settings &gt; System</strong> when you need a point-in-time restore package, not just a database backup</li>
</ul>

<div class="kb-callout-warning kb-callout">
<strong>Important:</strong> Backups include your database data only. Make sure to also backup your uploaded files (photos, documents) separately if using local storage.
</div>',
                    ],
                    [
                        'slug' => 'gdpr',
                        'title' => __('GDPR Compliance Tools'),
                        'summary' => __('Export and anonymize user and contact data for privacy compliance.'),
                        'tags' => ['GDPR', 'privacy', 'data export', 'anonymize', 'compliance', 'right to erasure'],
                        'body' => '<h3>Overview</h3>
<p>InsulaCRM provides built-in tools to help you comply with GDPR and other data protection regulations. Access these in <strong>Settings &gt; GDPR</strong>.</p>

<h3>Available Tools</h3>

<h4>Export User Data (Article 15 &mdash; Right of Access)</h4>
<p>Generate a comprehensive JSON file containing all data for a team member:</p>
<ul>
<li>Profile information (name, email, role)</li>
<li>All leads they created or are assigned to</li>
<li>Activities they logged</li>
<li>Tasks they created</li>
<li>Audit log entries for their actions</li>
</ul>

<h4>Anonymize User (Article 17 &mdash; Right to Erasure)</h4>
<p>Replace a user\'s personal data with anonymous placeholders and deactivate their account. This:</p>
<ul>
<li>Replaces name with "Anonymized User"</li>
<li>Clears email, phone, and other PII</li>
<li>Deactivates the account</li>
<li>Preserves the record structure for analytics</li>
</ul>

<h4>Export Contact Data</h4>
<p>Generate a JSON export for a specific lead/contact, including their property details, activities, and all associated records.</p>

<h4>Anonymize Contact</h4>
<p>Remove all personally identifiable information from a lead record while keeping it for statistical and analytical purposes.</p>

<h3>Audit Trail</h3>
<p>All GDPR actions are automatically recorded in the <strong>Audit Log</strong>, creating a compliance trail showing what data was exported or anonymized, by whom, and when.</p>

<div class="kb-callout">
<strong>Legal Note:</strong> These tools assist with GDPR compliance but do not constitute legal advice. Consult with a data protection officer or legal professional to ensure your specific usage complies with applicable regulations.
</div>',
                    ],
                    [
                        'slug' => 'notifications-settings',
                        'title' => __('Notification & Language Settings'),
                        'summary' => __('Configure notification preferences and manage translation files.'),
                        'tags' => ['notifications', 'language', 'localization', 'translation', 'i18n'],
                        'body' => '<h3>Notification Preferences</h3>
<p>Configure which events trigger notifications in <strong>Settings &gt; Notifications</strong>. Toggle notifications on/off for:</p>
<ul>
<li>New lead assignments</li>
<li>Deal stage changes</li>
<li>Task due date reminders</li>
<li>' . (BusinessModeService::isRealEstate() ? 'Client match alerts' : 'Buyer match alerts') . '</li>
<li>Sequence completions</li>
</ul>

<h3>Language & Localization</h3>
<p>InsulaCRM supports multiple languages. Manage translations in <strong>Settings &gt; Languages</strong>:</p>

<h4>Viewing Available Languages</h4>
<p>The language manager lists all installed language files with their completion percentage.</p>

<h4>Editing Translations</h4>
<p>Click any language to open the translation editor. You\'ll see all translatable strings with their current translations. Edit inline and save.</p>

<h4>Uploading Language Files</h4>
<p>Upload a complete language JSON file to add a new language or replace an existing one. The file format follows Laravel\'s JSON translation convention:</p>
<pre style="background: rgba(98,105,118,.08); padding: 0.75rem; border-radius: 4px; font-size: 0.85rem;"><code>{
    "Dashboard": "Tableau de bord",
    "Leads": "Prospects",
    "Settings": "Param&egrave;tres"
}</code></pre>

<h4>Setting the Default Language</h4>
<p>The system language is set in General Settings. Individual users can override it from their Profile page.</p>',
                    ],
                ],
            ],

            // ─── PLUGINS ─────────────────────────────────────────────────
            [
                'name' => __('Plugins'),
                'icon' => 'plug',
                'articles' => [
                    [
                        'slug' => 'plugin-system',
                        'title' => __('Plugin System Overview'),
                        'summary' => __('Extend InsulaCRM with plugins for SMS, integrations, and custom features.'),
                        'tags' => ['plugins', 'extensions', 'hooks', 'customization'],
                        'body' => '<h3>Overview</h3>
<p>InsulaCRM has a powerful plugin system that lets you extend functionality without modifying core code. Plugins can add new pages, sidebar menu items, dashboard widgets, settings tabs, and hook into CRM events.</p>

<h3>Managing Plugins</h3>
<p>Go to <strong>Plugins</strong> in the sidebar (admin only) to see all installed plugins. Each plugin shows:</p>
<ul>
<li>Name, version, and author</li>
<li>Description of what it does</li>
<li>Active/inactive status toggle</li>
</ul>

<h3>Installing a Plugin</h3>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Obtain the plugin as a <code>.zip</code> file</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Go to <strong>Plugins</strong> and click <strong>Upload Plugin</strong></div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">Select the .zip file and upload</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">The plugin is installed but <strong>inactive</strong> by default &mdash; toggle it on when ready</div></div>

<h3>Plugin Capabilities</h3>
<p>Plugins can:</p>
<ul>
<li><strong>Add sidebar menu items</strong> &mdash; Custom pages accessible from the navigation</li>
<li><strong>Add dashboard widgets</strong> &mdash; Extra widgets on the main dashboard</li>
<li><strong>Add settings tabs</strong> &mdash; Configuration pages in the settings area</li>
<li><strong>Register routes</strong> &mdash; Custom URL endpoints under <code>/plugin/{slug}/</code></li>
<li><strong>Hook into events</strong> &mdash; React to lead creation, deal stage changes, etc.</li>
<li><strong>Filter data</strong> &mdash; Modify data before it\'s saved or displayed</li>
</ul>

<h3>Available Hooks</h3>
<table>
<thead><tr><th>Hook</th><th>Fires When</th></tr></thead>
<tbody>
<tr><td><code>lead.created</code></td><td>A new lead is created</td></tr>
<tr><td><code>lead.updated</code></td><td>Lead details are modified</td></tr>
<tr><td><code>lead.status_changed</code></td><td>Lead status changes</td></tr>
<tr><td><code>deal.stage_changed</code></td><td>Deal moves to a new stage</td></tr>
<tr><td><code>activity.logged</code></td><td>An activity is recorded</td></tr>
<tr><td><code>buyer.notified</code></td><td>' . (BusinessModeService::isRealEstate() ? 'A client receives a listing notification' : 'A buyer receives a deal notification') . '</td></tr>
<tr><td><code>sequence.step_executed</code></td><td>A drip sequence step runs</td></tr>
</tbody>
</table>

<h3>Uninstalling Plugins</h3>
<p>To remove a plugin, first deactivate it, then click <strong>Uninstall</strong>. This deletes the plugin files from the server. Any data created by the plugin may remain in the database.</p>

<div class="kb-callout-warning kb-callout">
<strong>Caution:</strong> Only install plugins from trusted sources. Plugins have full access to your CRM data and server resources.
</div>',
                    ],
                ],
            ],

            // ─── TROUBLESHOOTING ─────────────────────────────────────────
            [
                'name' => __('Troubleshooting'),
                'icon' => 'bug',
                'articles' => [
                    [
                        'slug' => 'error-reports',
                        'title' => __('Viewing & Reporting Errors'),
                        'summary' => __('Access captured error logs, export bug reports, and send them to support.'),
                        'tags' => ['errors', 'bugs', 'troubleshooting', 'support', 'debug'],
                        'body' => '<h3>Automatic Error Capture</h3>
<p>InsulaCRM automatically captures all application errors and logs them to the <strong>Bug Reports</strong> page. When something goes wrong, the system records:</p>
<ul>
<li>Error message and exception type</li>
<li>File and line number where the error occurred</li>
<li>Full stack trace</li>
<li>URL that was being accessed</li>
<li>HTTP method (GET, POST, etc.)</li>
<li>User who triggered the error</li>
<li>IP address and user agent</li>
<li>Request context (form data, excluding passwords)</li>
</ul>

<h3>Viewing Errors</h3>
<p>Go to <strong>Bug Reports</strong> in the sidebar (admin only). The list shows all captured errors with:</p>
<ul>
<li><strong>Level</strong> &mdash; Error (orange), Warning (yellow), or Critical (red)</li>
<li><strong>Message</strong> &mdash; Short description of what went wrong</li>
<li><strong>File</strong> &mdash; Which file caused the error</li>
<li><strong>When</strong> &mdash; Relative timestamp</li>
<li><strong>Status</strong> &mdash; Open or Resolved</li>
</ul>

<h4>Filtering</h4>
<p>Use the filter bar to narrow down errors by:</p>
<ul>
<li>Level (Error, Warning, Critical)</li>
<li>Status (Unresolved, Resolved)</li>
<li>Search text (matches message, URL, and file name)</li>
</ul>

<h3>Exporting a Bug Report</h3>
<p>To report an issue to support:</p>
<div class="kb-step"><span class="kb-step-num">1</span><div class="kb-step-content">Click on an error to view its details</div></div>
<div class="kb-step"><span class="kb-step-num">2</span><div class="kb-step-content">Click <strong>Export Bug Report</strong></div></div>
<div class="kb-step"><span class="kb-step-num">3</span><div class="kb-step-content">A JSON file downloads containing the full error details, stack trace, and system information (PHP version, Laravel version, OS, database driver)</div></div>
<div class="kb-step"><span class="kb-step-num">4</span><div class="kb-step-content">Send this file to support for fast debugging</div></div>

<h3>Managing Errors</h3>
<ul>
<li><strong>Mark Resolved</strong> &mdash; Click to mark an error as fixed. It stays in the list for reference.</li>
<li><strong>Reopen</strong> &mdash; If the error recurs, mark it as open again.</li>
<li><strong>Clear Resolved</strong> &mdash; Permanently delete all resolved errors to clean up the list.</li>
</ul>',
                    ],
                    [
                        'slug' => 'common-issues',
                        'title' => __('Common Issues & Solutions'),
                        'summary' => __('Fixes for common error messages and problems you may encounter.'),
                        'tags' => ['FAQ', 'troubleshooting', 'errors', 'help', '419', '403', '500'],
                        'body' => '<h3>419 &mdash; Page Expired</h3>
<p><strong>Cause:</strong> Your session timed out or the CSRF token expired (usually from leaving a form open too long).</p>
<p><strong>Fix:</strong> Refresh the page and try again. If it persists, log out and log back in.</p>

<h3>403 &mdash; Forbidden</h3>
<p><strong>Cause:</strong> Your user role doesn\'t have permission to access this page.</p>
<p><strong>Fix:</strong> Check your role in Profile. Contact your admin if you believe you should have access.</p>

<h3>500 &mdash; Server Error</h3>
<p><strong>Cause:</strong> An unexpected error on the server. This is automatically logged to Bug Reports.</p>
<p><strong>Fix:</strong> Check Bug Reports for the specific error. Common causes:</p>
<ul>
<li>Database connection lost &mdash; verify MySQL/MariaDB is running</li>
<li>Disk full &mdash; check server disk space</li>
<li>Permission issue &mdash; check file permissions on <code>storage/</code> and <code>bootstrap/cache/</code></li>
</ul>

<h3>Emails Not Sending</h3>
<p><strong>Cause:</strong> SMTP misconfiguration or provider blocking.</p>
<p><strong>Fix:</strong></p>
<ul>
<li>Verify SMTP settings in Settings &gt; Email</li>
<li>Click "Send Test Email" to check the connection</li>
<li>If using Gmail, ensure "App Passwords" is set up (regular passwords don\'t work with 2FA)</li>
<li>Check that port 587 (TLS) or 465 (SSL) is not blocked by your server\'s firewall</li>
</ul>

<h3>Leads Not Being Distributed</h3>
<p><strong>Cause:</strong> Distribution not configured or no eligible agents.</p>
<p><strong>Fix:</strong></p>
<ul>
<li>Check Settings &gt; ' . (BusinessModeService::isRealEstate() ? 'Lead Routing' : 'Distribution') . ' &mdash; ensure a method is selected</li>
<li>Verify there are active agents with the right roles</li>
<li>For Hybrid mode, ensure the scheduled task <code>leads:assign-unclaimed</code> is running</li>
</ul>

<h3>AI Features Not Working</h3>
<p><strong>Cause:</strong> AI not configured, disabled, or API key issue.</p>
<p><strong>Fix:</strong></p>
<ul>
<li>Go to Settings &gt; AI and verify the provider and API key</li>
<li>Click "Test Connection" &mdash; it should return a success message</li>
<li>Check that AI is toggled ON</li>
<li>For Ollama, verify the Ollama server is running and accessible at the configured URL</li>
</ul>

<h3>CSV Import Failing</h3>
<p><strong>Cause:</strong> File format issues.</p>
<p><strong>Fix:</strong></p>
<ul>
<li>Ensure the file is UTF-8 encoded (not UTF-16 or Windows-1252)</li>
<li>Use comma delimiters (not semicolons or tabs)</li>
<li>First row must be column headers</li>
<li>Check file size against PHP\'s <code>upload_max_filesize</code> limit</li>
</ul>

<h3>Slow Performance</h3>
<p><strong>Fix:</strong></p>
<ul>
<li>Check <strong>Settings &gt; System</strong> for database connection health</li>
<li>Ensure PHP OPcache is enabled</li>
<li>Review active plugins &mdash; disable any unnecessary ones</li>
<li>Check server resources (CPU, RAM, disk I/O)</li>
<li>For large databases (10,000+ leads), ensure database indexes are intact</li>
</ul>

<h3>System Health Check</h3>
<p>Go to the <strong>System Health</strong> section on the main <strong>Settings</strong> page to see:</p>
<ul>
<li>PHP version and required extensions</li>
<li>Database connection status</li>
<li>Storage directory permissions</li>
<li>Scheduled task status</li>
<li>Active plugin count and health</li>
<li>API request logs</li>
</ul>',
                    ],
                    [
                        'slug' => 'scheduled-tasks',
                        'title' => __('Scheduled Tasks & Cron Jobs'),
                        'summary' => __('Set up the server cron job and understand what runs automatically.'),
                        'tags' => ['cron', 'schedule', 'automation', 'server', 'artisan'],
                        'body' => '<h3>Setting Up the Cron Job</h3>
<p>InsulaCRM requires a single cron entry on your server to run all scheduled tasks. Add this to your server\'s crontab:</p>
<pre style="background: rgba(98,105,118,.08); padding: 0.75rem; border-radius: 4px; font-size: 0.85rem;"><code>* * * * * cd /path/to/insulacrm && php artisan schedule:run >> /dev/null 2>&1</code></pre>
<p>Replace <code>/path/to/insulacrm</code> with the actual path to your installation.</p>

<h3>Scheduled Commands</h3>
<table>
<thead><tr><th>Command</th><th>Frequency</th><th>Description</th></tr></thead>
<tbody>
<tr><td><code>sequences:process</code></td><td>Daily</td><td>Processes drip sequence steps for all enrolled leads</td></tr>
<tr><td><code>deals:check-due-diligence</code></td><td>Daily</td><td>Sends alerts for ' . (BusinessModeService::isRealEstate() ? 'transactions approaching their deadline' : 'deals approaching their due diligence deadline') . '</td></tr>
<tr><td><code>leads:assign-unclaimed</code></td><td>Every minute</td><td>Auto-assigns unclaimed leads in Hybrid distribution mode</td></tr>
<tr><td><code>backup:clean</code></td><td>Daily at 1 AM</td><td>Removes old backup files to prevent disk space issues</td></tr>
</tbody>
</table>

<h3>Verifying the Cron Job</h3>
<p>To check if your cron is running properly:</p>
<ul>
<li>Check <strong>Settings &gt; System</strong> for scheduled task status</li>
<li>Look for recent sequence processing in the audit log</li>
<li>Verify leads are being auto-assigned (if using Hybrid distribution)</li>
</ul>

<div class="kb-callout-warning kb-callout">
<strong>Important:</strong> Without the cron job, drip sequences won\'t process, ' . (BusinessModeService::isRealEstate() ? 'transaction deadline alerts won\'t fire' : 'due diligence alerts won\'t fire') . ', and hybrid lead distribution won\'t auto-assign. This is the single most important server configuration step.
</div>',
                    ],
                ],
            ],
        ];
    }
}

