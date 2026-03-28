# Upgrade Guide

Use this process for normal upgrades. A full reinstall is not required.

## Preferred Upgrade Path

If you are running v1.0.1 or later, the safest path is now the built-in updater:

Before updating production, test the same release on a staging copy of your CRM whenever possible. Confirm that your plugins, integrations, customizations, queues, and critical workflows still behave as expected, then schedule the production update.

1. Open `Settings > System`
2. Upload the official InsulaCRM release ZIP
3. Review the staged package warnings
4. Click `Backup and Apply Update`

The product will:

- create a fresh database backup automatically
- create a recovery snapshot immediately before patching begins
- preserve `.env`, `storage/`, `public/storage`, and `plugins/`
- place the app into maintenance mode briefly
- patch the application files
- run migrations and cache clears
- run a post-update health check

## About Recovery Snapshots

Recovery snapshots are point-in-time restore points created immediately before the updater starts patching your CRM. They exist to recover the last known-good state if an upgrade fails badly.

Use snapshots carefully:

- restore a snapshot only when necessary
- restoring a snapshot replaces code and database changes created after the snapshot time
- the best time to create a snapshot is right before the upgrade starts so the amount of later data you might lose stays as small as possible
- when you apply or restore an update or snapshot from the UI, keep the page open until the process finishes; the product now shows a waiting overlay and blocks duplicate submits to reduce operator error
- snapshot restores support `.sql` and `.sql.gz` database backups on Windows as well as Linux

Admins can also create manual recovery snapshots from `Settings > System` before risky maintenance, custom development, or major configuration changes. Use the same rule there: create the snapshot as late as possible so it represents the most recent safe state.

Use the manual process below only when you cannot use the in-app updater.

## Before You Start

Back up the following before replacing any files:

- database
- `.env`
- `storage/`
- uploaded files
- custom plugins or local modifications

If you do not have a staging environment, treat the automatic database backup created by the built-in updater as your minimum safety requirement and perform the production upgrade during a low-risk maintenance window.

If you use the built-in backup tool, run:

```bash
php artisan backup:run
```

## Manual Upgrade Steps

1. Download the latest InsulaCRM release from the website.
2. Put the application into maintenance mode if your deployment process requires it:
   ```bash
   php artisan down
   ```
3. Replace the application files with the new release while preserving:
   - `.env`
   - `storage/`
   - `plugins/`
   - any custom uploads or deployment-specific files
4. If you are deploying from source or intentionally rebuilding dependencies, run:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
5. Run the deployment helper (recommended) or the upgrade commands manually:
   ```bash
   # Option A: deployment helper (runs all steps including OPcache flush)
   bash scripts/deploy.sh

   # Option B: manual commands
   php artisan migrate --force
   php artisan optimize:clear
   php artisan queue:restart
   sudo systemctl reload php8.4-fpm   # flush OPcache — adjust for your PHP version
   ```
6. Bring the application back online:
   ```bash
   php artisan up
   ```
7. Verify login, dashboard, settings, and plugin behavior.

## Important Notes

- Do not rerun the installer for a normal upgrade.
- Do not overwrite your `.env` file.
- Do not delete `storage/` or user uploads.
- The built-in updater is designed to reduce risk by taking a backup and a recovery snapshot before patching and preserving protected runtime paths.
- The built-in updater reduces upgrade risk, but it does not replace staging validation for production environments.
- The packaged release already includes runtime dependencies, so Composer is optional unless you are rebuilding dependencies or deploying from source.
- The app version is read from the root `VERSION` file, and the System tab can compare it against the latest release metadata published on the website.
- If `storage/installed.lock` is missing but the application database already contains the expected tenant and user records, InsulaCRM will recreate the marker automatically instead of forcing the installer again.

## Recommended Post-Upgrade Checks

- Confirm the login page and dashboard load
- Confirm background workers were restarted
- Confirm `APP_URL` still matches the public URL
- Confirm installed plugins are still present and compatible

## Version-Specific Notes

### Upgrading to v1.0.1

Security and quality audit release. Includes the new permissions system, performance indexes, and security fixes.

1. Run `php artisan migrate --force` to apply new migrations.
2. Run `php artisan db:seed --class=Database\\Seeders\\BaseSeeder --force` to ensure default roles exist with the `is_system` flag.
3. Clear caches and restart queue workers.

### Permissions System

v1.0.1 introduced granular permissions. Existing roles are migrated with their default permission sets:

- **Admin**: all permissions granted
- **Agent**: leads, properties, pipeline, calendar, profile
- **Acquisition Agent**: leads, properties, pipeline, calendar, profile
- **Disposition Agent**: pipeline, buyers, calendar, profile
- **Field Scout**: properties view/create, profile

Custom roles created after the migration start with no permissions and should be configured in **Settings > Roles & Permissions**.

## Troubleshooting Upgrades

- **Migration fails**: Check `storage/logs/laravel.log` for the specific error. Common causes are missing PHP extensions, database connection issues, or insufficient permissions.
- **Blank page after upgrade**: Clear all caches and verify `APP_KEY` is set in `.env`.
- **Queue jobs failing**: Restart all queue workers after upgrading. Old serialized jobs may be incompatible with new code.
- **Unexpected installer redirect**: Verify the existing production database is still connected and contains the tenant/user data. The install marker should be recreated automatically for a healthy existing installation.
