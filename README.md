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

### 1) Add the plugin repository to Mautic `composer.json`

Add this entry to the `repositories` section:

```json
{
  "type": "vcs",
  "url": "https://github.com/mauticinsights/mautic-cron-orchestrator"
}
```

### 2) Install the plugin with Composer

Run this in the root of your Mautic project:

```bash
composer require mauticinsights/mautic-cron-orchestrator
```

If you are installing from a branch without a release tag:

```bash
composer require mauticinsights/mautic-cron-orchestrator:dev-main -W
```

### 3) Clear cache and reload plugins

```bash
php bin/console cache:clear --env=prod
php bin/console mautic:plugins:reload
```

### 4) Activate in Mautic

- open Plugins page in the Mautic UI
- find **Mautic Cron Orchestrator**
- open **Cron Jobs** in the menu (permission: `orchestrator:crons:view`)
- apply a preset (Minimal / Standard / Full) or add custom jobs

### 5) System cron

Replace individual Mautic cron lines with:

```
* * * * * php /path/to/mautic/bin/console mautic:cron:run
```

Useful options:

```bash
php bin/console mautic:cron:run --dry-run
php bin/console mautic:cron:run --cleanup-only
```

## Development

```bash
composer install
make ci          # cs-check + phpstan + phpunit
make all         # cs-fixer + phpstan + test
```

## Verify It Works

- apply a preset and confirm jobs appear in **Cron Jobs**
- run `php bin/console mautic:cron:run --dry-run` and check due jobs
- trigger a job manually from the UI and inspect recent run logs
- confirm `logs/` / Mautic logger has no unexpected orchestrator errors

## License

This project is licensed under the [MIT License](./LICENSE).
