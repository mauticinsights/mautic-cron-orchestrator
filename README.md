# Mautic Cron Orchestrator

A plugin for Mautic 5.x that replaces dozens of system cron entries with a **single** orchestrator command. Jobs (segments, campaigns, broadcasts, webhooks, …) are configured in the Mautic UI and run on a schedule stored in the database.

## What This Plugin Does

- runs due maintenance jobs via `mautic:cron:run` (one system cron every minute)
- ships presets: **minimal**, **standard**, **full**
- provides an admin UI to enable/disable, edit, run, and apply presets
- logs each run and retains history according to plugin settings

## Requirements

- PHP 8.0+
- Mautic 5.x
- Composer 2.x

## Installation

### Recommended (Packagist)

From the root of your Mautic project:

```bash
composer require mauticinsights/mautic-cron-orchestrator
```

Package: [packagist.org/packages/mauticinsights/mautic-cron-orchestrator](https://packagist.org/packages/mauticinsights/mautic-cron-orchestrator)

### Alternative: install from GitHub (VCS)

Only needed if you want a branch / unreleased commit. Add to the `repositories` section of Mautic’s `composer.json`:

```json
{
  "type": "vcs",
  "url": "https://github.com/mauticinsights/mautic-cron-orchestrator"
}
```

Then:

```bash
composer require mauticinsights/mautic-cron-orchestrator:dev-main -W
```

### After install

```bash
php bin/console cache:clear --env=prod
php bin/console mautic:plugins:reload
```

### Activate in Mautic

- open **Settings** (gear icon in the top-right) → **Plugins**
- open **Mautic Cron Orchestrator** / **Cron Orchestrator**
- turn **Enabled** to **Yes** and save (required — when disabled, the Cron Jobs UI is hidden and `mautic:cron:run` does nothing)
- then open **Settings** → **Cron Jobs** (admin users only)

### System cron

Remove the individual Mautic cron lines (`mautic:segments:update`, `mautic:campaigns:trigger`, …) so jobs do not run twice.

**User crontab** (`crontab -e` as the web user, e.g. `www-data`) — 5 time fields, then the command:

```
* * * * * /usr/bin/php /path/to/mautic/bin/console mautic:cron:run
```

**System crontab** (`/etc/crontab` or `/etc/cron.d/…`) — 5 time fields, then **username**, then the command:

```
* * * * * www-data /usr/bin/php /path/to/mautic/bin/console mautic:cron:run
```

Use the absolute path to `php` (`which php`). On Debian/Ubuntu the web user is usually `www-data`.

Useful options:

```bash
php bin/console mautic:cron:run --dry-run
php bin/console mautic:cron:run --cleanup-only
```

## Usage

On first install the plugin seeds the **Standard** preset automatically. After that, manage jobs in the Mautic UI:

1. Go to **Settings** (top-right gear) → **Cron Jobs**.
2. Apply a preset (**Minimal** / **Standard** / **Full**) or click **Add custom job**.
3. For each job you can enable/disable, edit frequency, run immediately, or delete.
4. Open a job to see recent run logs.

**Applying a preset** replaces all jobs from built-in presets (`minimal` / `standard` / `full`). Custom jobs you added yourself are left untouched.

Presets follow the official [Mautic cron jobs](https://docs.mautic.org/en/5.2/configuration/cron_jobs.html) guide:

| Preset | Jobs | Typical use |
|---|---|---|
| **Minimal** | Required: segments, campaign update/trigger, messages, custom-field columns | Small installs |
| **Standard** | Minimal + broadcasts, webhooks, import, bounce fetch, reports | Most production sites |
| **Full** | Standard + GeoIP, maintenance cleanup, MaxMind CCPA, unused IPs, social, contact export | Full feature set / compliance |

Core segment/campaign frequencies are staggered (15 / 20 / 25 min). Every preset job includes `--no-interaction --no-ansi`.

### Plugin Enabled switch

In **Settings → Plugins → Cron Orchestrator**, use the standard **Enabled** Yes/No control (same as other Mautic plugins).

| Enabled | Behaviour |
|---|---|
| **Yes** | Cron Jobs menu is visible (admins), UI works, `mautic:cron:run` executes due jobs |
| **No** | Cron Jobs menu is hidden, UI URLs return access denied, `mautic:cron:run` exits without running jobs |

The UI (`/s/cron`) also requires an **admin** user. Non-admins never see the menu and get access denied on direct URLs.

The orchestrator decides which configured jobs are due each minute; you only need the one system cron from **System cron** above.

## Development

```bash
composer install
make ci          # cs-check + phpstan + phpunit
make all         # cs-fixer + phpstan + test
```

## Verify It Works

- enable the plugin in **Settings → Plugins** (Enabled = Yes)
- open **Settings → Cron Jobs**, apply a preset, and confirm jobs appear
- run `php bin/console mautic:cron:run --dry-run` and check due jobs
- trigger a job manually from the UI and inspect recent run logs
- confirm Mautic logs have no unexpected orchestrator errors
- disable the plugin and confirm the Cron Jobs menu disappears and `mautic:cron:run` reports it is disabled

## License

This project is licensed under the [MIT License](./LICENSE).
