# InsulaCRM - Installation Guide

This guide covers everything you need to install and configure InsulaCRM on your server. Four installation methods are provided: a guided web installer, a supported Linux shell installer, a manual CLI setup, and Docker.

---

## Table of Contents

1. [Server Requirements](#server-requirements)
2. [Installation Method 1: Web Installer (Recommended)](#method-1-web-installer-recommended)
3. [Installation Method 2: Supported Linux Shell Installer](#method-2-supported-linux-shell-installer)
4. [Installation Method 3: Manual CLI Install](#method-3-manual-cli-install)
5. [Installation Method 4: Docker](#method-4-docker)
6. [Web Server Configuration](#web-server-configuration)
   - [Deployment Modes](#deployment-modes)
   - [Apache Virtual Host](#apache-virtual-host)
   - [Apache Subfolder Alias](#apache-subfolder-alias)
   - [Shared Hosting / No VHost Access](#shared-hosting--no-vhost-access)
   - [Nginx Server Block](#nginx-server-block)
7. [Cron Job Setup](#cron-job-setup)
8. [Queue Worker Setup](#queue-worker-setup)
9. [S3 Storage (Optional)](#s3-storage-optional)
10. [SMTP / Email (Optional)](#smtp--email-optional)
11. [Troubleshooting](#troubleshooting)
12. [Upgrading](#upgrading)
14. [Demo Credentials](#demo-credentials)

---

## Deployment Model

InsulaCRM is designed for **self-hosted, owner-controlled deployments**. Each installation should represent a single organization where the admin user is the server owner or their trusted delegate.

Administrative features — plugin installation, in-app updates, snapshot restore, and language file editing — operate on the entire application installation. This is intentional: the admin controls their own server.

InsulaCRM is **not designed for shared multi-tenant SaaS deployments** where unrelated organizations share one application instance. In that architecture, admin-level operations would cross trust boundaries between organizations.

---

## Server Requirements

Before installing InsulaCRM, verify that your server meets the following minimum requirements.

### PHP

- **PHP 8.2 or higher**
- Required extensions:
  - BCMath
  - Ctype
  - cURL
  - DOM
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO (with MySQL driver)
  - Tokenizer
  - XML
  - GD

Most hosting providers and standard PHP installations include these extensions by default. You can verify installed extensions by running:

```bash
php -m
```

Or create a temporary `phpinfo.php` file in your web root:

```php
<?php phpinfo();
```

### Database

One of the following:

- **MySQL 8.0+** (recommended)
- **MariaDB 10.6+**

### Web Server

- **Apache** with `mod_rewrite` enabled, OR
- **Nginx**

### Build Tools

- **Composer 2.x** -- [https://getcomposer.org](https://getcomposer.org) (optional for the standard packaged install, required only if you want to reinstall PHP dependencies or deploy from source)
- **Node.js 18+** and **npm** -- [https://nodejs.org](https://nodejs.org) (optional, only needed if you plan to modify front-end assets or use the development workflow)

---

## Method 1: Web Installer (Recommended)

The web installer is the easiest way to get InsulaCRM running. The packaged release already includes the production `vendor/` directory, so no Composer or Node.js step is required for a standard install.

Before you upload the package, decide which deployment mode you are using:

- **Recommended:** point the root domain or subdomain document root directly at `public/`. This is the simplest and preferred deployment path.
- **Supported for advanced users:** keep the package in a folder such as `/public_html/demo` and expose it at `/demo`. This may require Apache or Nginx alias/rewrite configuration depending on the hosting environment.
- **Shared hosting fallback:** use the included project-root `index.php` and `.htaccess` when you cannot change the document root, but the server must still route requests into the Laravel entrypoint correctly.

### Step-by-Step

1. **Upload files to your server.**

   Extract the InsulaCRM archive and upload all files to your desired directory on the server (for example, `/var/www/insulacrm`).

2. **Choose the correct web-root strategy.**

   The recommended production setup is to point your web server's document root to the `public/` folder inside the project, not the project root.

   If you must install into a subfolder such as `/demo`, `/crm`, or `/abc`, follow the dedicated examples in [Web Server Configuration](#web-server-configuration) below. Subfolder installs are supported, but they are an advanced deployment mode and may require server-level configuration. The package includes a project-root `index.php` and root `.htaccess` for shared-hosting deployments where the host serves the project root directly.

3. **Open your browser and navigate to your domain.**

   The installer will launch automatically and guide you through five steps:

   - **Step 1 -- Requirements Check**
     The installer verifies PHP version, required extensions, `.env` availability, MySQL PDO support, writable directories, and the detected install URL. Any issues are listed with instructions to resolve them.

   - **Step 2 -- Database Configuration**
     Enter the existing database host, port, name, username, and password assigned to you by your hosting panel or server administrator. Automatic database-user creation is available as an advanced option for MariaDB administrator accounts on VPS or dedicated servers.

   - **Step 3 -- Company Name and Admin Account**
     Provide your company name and create the initial administrator account (name, email, and password).

   - **Step 4 -- Migration**
     Database tables are created automatically. This step runs without any input required from you.

   - **Step 5 -- Success**
     Installation is complete. A link to the login page is displayed.

4. **Optional: Load demo data.**

   During the installation wizard, you can check the **"Load demo data"** option. This populates your instance with sample records so you can explore the system immediately:

   - 200 leads
   - 50 deals
   - 20 buyers
   - 6 users (see [Demo Credentials](#demo-credentials) below)

   Demo data is optional. If sample data cannot be loaded, the base CRM installation can still complete.

5. **Log in and start using InsulaCRM.**

---

## Method 2: Supported Linux Shell Installer

Use this method on supported Linux servers where you have SSH access and want a guided terminal workflow. The included script targets the common VPS / Virtualmin path and then hands off to the product-side CLI installer.

### 1. Upload and Extract the Release

Upload the InsulaCRM release to your server and extract it into the target directory.

### 2. Make the Script Executable

```bash
chmod +x scripts/install.sh
```

### 3. Run the Installer

```bash
./scripts/install.sh
```

The script will:

- create `.env` from `.env.example` if needed
- prompt for the app URL, company, admin account, and database credentials
- write those values into `.env`
- apply the standard writable-path permissions for `storage/`, `bootstrap/cache/`, and `plugins/`
- run `php artisan app:install`
- run `php artisan system:doctor` at the end

### 4. Finish the Server Setup

After the script completes, point your web root at `public/`, add the Laravel scheduler cron entry, and start a queue worker for background jobs.

### 5. Troubleshoot if Needed

If the shell installer reports a problem, run:

```bash
php artisan system:doctor --strict
```

Then continue with the manual CLI method below if you need to inspect or rerun any step yourself.

---

## Method 3: Manual CLI Install

Use this method if you have SSH access to your server and prefer working from the command line.

### 1. Upload and Extract Files

Upload the InsulaCRM archive to your server and extract it:

```bash
cd /var/www
unzip insulacrm.zip -d insulacrm
cd insulacrm
```

### 2. Install PHP Dependencies (Optional)

If you are deploying from source or intentionally rebuilding dependencies, run:

```bash
composer install --optimize-autoloader --no-dev
```

The packaged release already includes the runtime dependencies required for both the core installer and optional demo data. Composer is only required when you intentionally rebuild dependencies or deploy from source.

### 3. Configure Environment

If you want a guided CLI flow instead of running each step manually, you can stop after editing `.env` and run `php artisan app:install`. The remaining manual steps below show what that command automates.

Copy the example environment file and generate an application key:

Copy the example environment file and generate an application key:

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Edit the .env File

Open `.env` in a text editor and set your database credentials:

```dotenv
APP_NAME=InsulaCRM
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=insulacrm
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Seed Required Data

Seed the essential roles and permissions:

```bash
php artisan db:seed --class=Database\\Seeders\\BaseSeeder
```

### 7. Seed Demo Data (Optional)

To load sample data for testing and exploration:

```bash
php artisan db:seed
```

### 8. Create the Storage Symlink

```bash
php artisan storage:link
```

### 9. Set Directory Permissions

The web server must be able to write to the `storage/` and `bootstrap/cache/` directories:

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/ bootstrap/cache/
```

Replace `www-data` with the user your web server runs as (for example, `nginx`, `apache`, or `http`). On Linux XAMPP installs under `/opt/lampp`, Apache commonly runs as `daemon`, so for local testing you can use `chmod 666 .env` and `chmod -R 777 storage bootstrap/cache plugins` instead.

### 10. Run a Health Check

Use the built-in doctor command to confirm the instance is ready:

```bash
php artisan system:doctor
```

### 11. Point Your Web Root

Configure your web server to serve the `public/` directory as the document root. See [Web Server Configuration](#web-server-configuration) below.

---

## Method 4: Docker

A Docker setup is included for quick local development or containerized deployments.

### 1. Start the Containers

From the project root directory, run:

```bash
docker-compose up -d
```

This starts four services:

| Service  | Description         | Exposed Port |
|----------|---------------------|--------------|
| PHP-FPM  | Application runtime | (internal)   |
| Nginx    | Web server          | **8080**     |
| MySQL    | MySQL 8.0 database  | 3306         |
| Redis    | Cache and queues    | 6379         |

### 2. Access InsulaCRM

Open your browser and navigate to:

```
http://localhost:8080
```

The web installer will appear on first run. Follow the steps described in [Method 1](#method-1-web-installer-recommended).

### 3. Stopping the Containers

```bash
docker-compose down
```

To also remove volumes (including database data):

```bash
docker-compose down -v
```

---

## Web Server Configuration

### Deployment Modes

InsulaCRM supports three deployment patterns:

1. **Root domain or subdomain**
   Example: `https://crm.example.com`
   Best option. Point the vhost/server block directly at `/path/to/insulacrm/public`.

2. **Subfolder install**
   Example: `https://example.com/demo`
   Supported, but the web server must map `/demo` cleanly into the project without forcing `/public` into the final URL.

3. **Shared hosting fallback**
   Example: upload the package to `/public_html/demo`
   If you cannot edit Apache/Nginx vhosts, use the included root `index.php` and root `.htaccess`. This is less ideal than a proper document root, but it is supported for packaged deployments and is designed to survive normal file-replacement redeploys.

### Apache Virtual Host

Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Create or edit a virtual host configuration file (for example, `/etc/apache2/sites-available/insulacrm.conf`):

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com

    DocumentRoot /var/www/insulacrm/public

    <Directory /var/www/insulacrm/public>
        AllowOverride All
        Require all granted

        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/insulacrm-error.log
    CustomLog ${APACHE_LOG_DIR}/insulacrm-access.log combined
</VirtualHost>
```

Enable the site and restart Apache:

```bash
sudo a2ensite insulacrm.conf
sudo systemctl restart apache2
```

### Apache Subfolder Alias

Use this when the app must live at a path such as `/demo` instead of the domain root.

```apache
Alias /demo /var/www/insulacrm/public

<Directory /var/www/insulacrm/public>
    AllowOverride All
    Require all granted
    Options -Indexes +FollowSymLinks
</Directory>
```

If you are intentionally serving the full project directory at `/demo` on shared hosting instead of aliasing `public/`, upload the package into that folder and let the included root `.htaccess` and `index.php` route requests into `public/`.

Important notes for subfolder installs:

- Visit the app as `https://example.com/demo`, not `https://example.com/demo/public`
- The installer will auto-detect and save `APP_URL` with the `/demo` suffix
- Static assets must resolve from `/demo/...`, not `/demo/public/...`
- Re-uploading a newer package into the same folder should not require reapplying custom app-side rewrite rules
- If you see redirect loops or `404` responses before the installer loads, your web server is still pointing at the wrong path

### Shared Hosting / No VHost Access

This is the simplest low-friction path when the host only lets you upload files into a folder.

1. Upload the package contents into a folder such as `public_html/demo`
2. Keep the supplied root `.htaccess` and root `index.php` in place
3. Browse to `https://example.com/demo/install`
4. Complete the installer
5. Confirm that login, installer redirects, and assets all load without `/public` in the URL

If your host lets you change the document root, prefer pointing it at `public/` instead.

### Nginx Server Block

Create or edit a server block configuration file (for example, `/etc/nginx/sites-available/insulacrm`):

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    root /var/www/insulacrm/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and restart Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/insulacrm /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## Cron Job Setup

InsulaCRM relies on Laravel's task scheduler for background operations. You must add a single cron entry on your server.

Open the crontab editor:

```bash
crontab -e
```

Add the following line:

```
* * * * * cd /var/www/insulacrm && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/var/www/insulacrm` with the actual path to your InsulaCRM installation.

The scheduler handles the following automated tasks:

- **Drip sequences** -- sends scheduled follow-up emails and notifications to leads
- **Backup cleanup** -- removes expired database and file backups
- **Due diligence alerts** -- notifies agents of upcoming deadlines and required actions

---

## Queue Worker Setup

InsulaCRM uses queues to process jobs in the background (email sending, notifications, data imports, etc.). You need a queue worker running at all times in production.

### Basic Usage (Development / Testing)

```bash
php artisan queue:work --tries=3 --timeout=90
```

This command will keep running and process jobs as they arrive. Press `Ctrl+C` to stop it.

### Production Setup with Supervisor

For production environments, use [Supervisor](http://supervisord.org/) to keep the queue worker running and restart it automatically if it fails.

Install Supervisor:

```bash
# Debian / Ubuntu
sudo apt-get install supervisor

# CentOS / RHEL
sudo yum install supervisor
```

Create a configuration file at `/etc/supervisor/conf.d/insulacrm-worker.conf`:

```ini
[program:insulacrm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/insulacrm/artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/insulacrm/storage/logs/worker.log
stopwaitsecs=3600
```

Replace `/var/www/insulacrm` with your actual installation path and `www-data` with the user your web server runs as. On Linux XAMPP installs under `/opt/lampp`, Apache commonly runs as `daemon`, so for local testing you can use `chmod 666 .env` and `chmod -R 777 storage bootstrap/cache plugins` instead.

Start the workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start insulacrm-worker:*
```

After deploying updates, restart the workers so they pick up new code:

```bash
sudo supervisorctl restart insulacrm-worker:*
```

---

## S3 Storage (Optional)

InsulaCRM supports Amazon S3 (or S3-compatible services such as DigitalOcean Spaces and MinIO) for file storage. This is recommended for multi-server deployments and large file volumes.

### Option A: Configure via the Admin Panel

1. Log in as an administrator.
2. Navigate to **Settings > Storage**.
3. Select **S3** as the storage driver.
4. Enter your bucket name, region, access key, and secret key.
5. Save the settings.

### Option B: Configure via .env

Add or update the following variables in your `.env` file:

```dotenv
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket-name.s3.amazonaws.com
```

---

## SMTP / Email (Optional)

InsulaCRM can send transactional emails (lead notifications, drip campaigns, password resets, etc.) via any SMTP provider.

### Option A: Configure via the Admin Panel

1. Log in as an administrator.
2. Navigate to **Settings > Email**.
3. Enter your SMTP host, port, username, password, and encryption type.
4. Click the **"Send Test Email"** button to verify the configuration.
5. Save the settings.

### Option B: Configure via .env

Add or update the following variables in your `.env` file:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="InsulaCRM"
```

Common SMTP providers:

| Provider      | Host                    | Port | Encryption |
|---------------|-------------------------|------|------------|
| Gmail         | smtp.gmail.com          | 587  | TLS        |
| Mailgun       | smtp.mailgun.org        | 587  | TLS        |
| SendGrid      | smtp.sendgrid.net       | 587  | TLS        |
| Amazon SES    | email-smtp.us-east-1.amazonaws.com | 587 | TLS |
| Mailtrap      | sandbox.smtp.mailtrap.io | 2525 | TLS       |

---

## Troubleshooting

### The installer only works at `/public`

Cause: the web server is exposing the Laravel `public/` directory directly in the URL instead of mapping requests cleanly to the app root or subfolder.

Fix:

1. Preferred: point the document root to `public/`
2. For `/demo` installs: use an Apache alias or equivalent server config so the public URL stays `/demo`
3. For shared hosting: keep the supplied root `.htaccess` and `index.php` in the project folder

The final user-facing URL should be:

- `https://example.com/install`
- or `https://example.com/demo/install`

It should not be:

- `https://example.com/public/install`
- `https://example.com/demo/public/install`

### The installer reports `.env` or MySQL driver issues

Cause: the package cannot write `.env`, or PHP is missing `pdo_mysql`.

Fix:

1. On Linux or macOS, run `ls -la` first because `.env.example` is a hidden dotfile and will not appear in a normal `ls` output
2. Ensure `.env` exists or that the project root is writable so the installer can create it from `.env.example`
3. If `.env.example` is missing after extraction, re-upload the release package or create `.env` manually from the sample values in this guide
4. Install the `pdo_mysql` extension
5. Re-open `/install/requirements` and confirm the installer environment checks pass

Example:

```bash
ls -la
cp .env.example .env
chmod 664 .env
```

Manual fallback `.env` starter:

```dotenv
APP_NAME=InsulaCRM
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=insulacrm
DB_USERNAME=root
DB_PASSWORD=
```

### Automatic database-user creation fails

Cause: the credentials can connect to MariaDB, but they do not have the privileges needed for `CREATE USER` or `GRANT`.

Fix:

1. Leave the advanced "Create a dedicated database user automatically" option disabled
2. Use the database name, username, and password already assigned to your app in the hosting panel
3. Only use the automatic-user option when you have MariaDB administrator credentials on a VPS or dedicated server

This is the expected path for many shared-hosting installs.

### Low-Friction Deployment Path

For the least fragile first-run experience, use one of these:

1. Point a domain or subdomain directly at `public/`
2. Install in a subfolder like `/demo` with an Apache alias that maps `/demo` to `public/`
3. If neither is possible, upload into the target folder and keep the included root bootstrap files in place

Before deploying, smoke test:

- `/install`
- `/login`
- `/instant-demo`
- asset loading without `/public`

### White Screen / Blank Page

1. Check the Laravel log file for error details:
   ```bash
   tail -100 storage/logs/laravel.log
   ```
2. Verify that `storage/` and `bootstrap/cache/` are writable by the web server:
   ```bash
   chmod -R 775 storage/ bootstrap/cache/
   chown -R www-data:www-data storage/ bootstrap/cache/
   ```
   On Linux XAMPP installs under `/opt/lampp`, Apache commonly runs as `daemon`, so for local testing use:
   ```bash
   chmod 666 .env
   chmod -R 777 storage/ bootstrap/cache/ plugins/
   ```
3. Ensure PHP error reporting is enabled in development. In your `.env` file:
   ```dotenv
   APP_DEBUG=true
   ```
   **Important:** Set `APP_DEBUG=false` on production servers.

### 500 Internal Server Error

1. Verify the application key is set. If `APP_KEY` in `.env` is empty, generate one:
   ```bash
   php artisan key:generate
   ```
2. Confirm the database is reachable with the credentials in your `.env` file:
   ```bash
   php artisan db:monitor
   ```
3. Clear all caches and try again:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

### Queue Not Processing / Jobs Stuck

1. Verify the cron job is installed and running:
   ```bash
   crontab -l
   ```
2. Confirm the queue worker is active:
   ```bash
   php artisan queue:work --tries=3 --timeout=90
   ```
3. If using Supervisor, check its status:
   ```bash
   sudo supervisorctl status insulacrm-worker:*
   ```
4. Review failed jobs:
   ```bash
   php artisan queue:failed
   ```

### Emails Not Sending

1. Verify SMTP settings in **Settings > Email** or in your `.env` file.
2. Use the **"Send Test Email"** button in the admin panel to confirm delivery.
3. Ensure the queue worker is running -- emails are dispatched via the queue.
4. Check the Laravel log for mailer exceptions:
   ```bash
   tail -50 storage/logs/laravel.log
   ```

### Assets Not Loading (CSS/JS Missing, Broken Images)

1. Create the storage symlink:
   ```bash
   php artisan storage:link
   ```
2. Verify that `APP_URL` in your `.env` matches the actual URL you are accessing.
3. If you intentionally modified the optional Vite/Tailwind starter assets, rebuild them:
   ```bash
   npm run build
   ```

### Permission Denied Errors

Set the correct ownership and permissions:

```bash
chmod -R 775 storage/ bootstrap/cache/
chown -R www-data:www-data storage/ bootstrap/cache/
```

Replace `www-data` with your web server's user. Common alternatives: `nginx`, `apache`, `http`, `nobody`. On Linux XAMPP installs under `/opt/lampp`, Apache commonly runs as `daemon`, so for local testing you can use `chmod 666 .env` and `chmod -R 777 storage bootstrap/cache plugins` instead.

---

## Upgrading

Normal upgrades do **not** require rerunning the installer.

InsulaCRM now treats an existing configured database as an installed app even if `storage/installed.lock` is missing, so a normal upgrade should remain on the application path rather than falling back into setup.

The preferred production workflow is to test the release on a staging copy of your CRM first, validate plugins, integrations, custom automations, and background jobs, and then upgrade production only after the staging test succeeds.

If you are already running v1.0.1 or later, the preferred upgrade path is the in-app updater in **Settings > System**:

1. Upload the official release ZIP
2. Let InsulaCRM stage the package and show any warnings
3. Click **Snapshot, Backup, and Apply Update**

The updater creates a fresh database backup and a point-in-time recovery snapshot automatically before patching the app, and preserves `.env`, `storage/`, `public/storage`, and `plugins/`.

If you do not have a staging environment, use the built-in updater, confirm the automatic database backup completed successfully, and perform the production upgrade during a low-risk maintenance window. The in-app updater reduces risk, but it does not replace staging validation for production systems.

Recovery snapshots are intended to return the CRM to the last known-good state if an upgrade fails badly. They should be created as late as possible, ideally immediately before the update starts, so any newer code or data you would lose during a restore is kept to a minimum.

If you need a restore point outside the updater, administrators can create a manual recovery snapshot in `Settings > System` before risky maintenance or custom work.

When you create, apply, or restore a snapshot or update from the UI, keep the page open until the action finishes. InsulaCRM now shows a waiting overlay and blocks duplicate submits so users do not accidentally queue the same action multiple times.

Snapshot restores support both `.sql` and `.sql.gz` backups on Windows and Linux. You do not need an external `gunzip` binary on Windows for restore operations triggered by the product.

For manual file-replacement upgrades, see [UPGRADE.md](UPGRADE.md).

---

## Demo Credentials

If you selected **"Load demo data"** during installation (or ran `php artisan db:seed`), the following accounts are available:

| Email                    | Password   | Role               |
|--------------------------|------------|--------------------|
| admin@demo.com           | password   | Admin              |
| agent@demo.com           | password   | Agent              |
| acquisition@demo.com     | password   | Acquisition Agent  |
| disposition@demo.com     | password   | Disposition Agent  |
| scout@demo.com           | password   | Field Scout        |
| agent2@demo.com          | password   | Agent              |

**Important:** If you loaded demo data on a production server, change all default passwords immediately or remove the demo accounts after you have finished exploring the system.

---

## Support

If you need help with InsulaCRM, please contact support through your normal purchase or distribution channel. Include the following information to help us assist you quickly:

- PHP version (`php -v`)
- Server type (Apache or Nginx) and operating system
- Contents of `storage/logs/laravel.log` (last 50 lines)
- Steps to reproduce the issue







