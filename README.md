# Ziad API Plugin

[![lint](https://github.com/ze2322/ziad-api-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/ze2322/ziad-api-plugin/actions/workflows/ci.yml)
[![WordPress 5.8+](https://img.shields.io/badge/wordpress-5.8%2B-21759b.svg)](https://wordpress.org/)
[![PHP 7.4+](https://img.shields.io/badge/php-7.4%2B-777bb4.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A WordPress plugin that fetches data from an external REST API and renders it as
a configurable table. The same cached dataset is exposed three ways: as a
Gutenberg block for editors, as an admin screen for site owners, and as a WP-CLI
command for deployment scripts.

---

## Features

- **Gutenberg block** — an "API Data Table" block with per-column visibility
  toggles in the sidebar, so an editor chooses what each placement shows.
- **Admin screen** — a dashboard page listing the current dataset, with a manual
  refresh button.
- **Transient caching** — responses are cached for one hour, so page views do not
  each trigger an outbound request. The refresh button and the CLI command both
  bypass the cache.
- **AJAX endpoint** — the front end reads the dataset without a full page load.
- **WP-CLI command** — `wp ziad-api refresh` for scheduled or post-deploy warming.
- **PSR-4 autoloading** via Composer, with a built-in fallback autoloader so the
  plugin still works when dropped in without running `composer install`.

---

## Requirements

- WordPress 5.8 or newer (the block editor APIs this uses landed in 5.8)
- PHP 7.4 or newer
- Composer and Node.js only if you intend to rebuild the block assets

The plugin checks both versions on activation and refuses to stay active if they
are not met, rather than failing later with a fatal error.

---

## Installation

### Option A — install the plugin as-is

#### 1. Clone the repository into your plugins directory

```bash
cd wp-content/plugins
```

```bash
git clone https://github.com/ze2322/ziad-api-plugin.git
```

#### 2. Activate the plugin

In the WordPress admin, go to **Plugins**, find **Ziad API Plugin**, and click
**Activate**.

No build step is required. The bundled fallback autoloader resolves the plugin's
classes when a Composer `vendor/` directory is not present.

#### 3. Confirm it is running

A **Ziad API** entry appears in the admin sidebar, and a **Settings** link
appears under the plugin name on the Plugins page.

---

### Option B — set up for development

#### 1. Clone the repository

```bash
git clone https://github.com/ze2322/ziad-api-plugin.git
```

```bash
cd ziad-api-plugin
```

#### 2. Generate the optimised Composer autoloader

```bash
composer install
```

#### 3. Install the JavaScript build tooling

```bash
npm install
```

#### 4. Build the block assets

```bash
npm run build
```

Use `npm start` instead for a watching development build.

#### 5. Run the checks the CI runs

```bash
php bin/check-psr4.php
```

```bash
composer validate --strict --no-check-lock
```

---

## Usage

### Adding the block to a page

1. Edit any post or page.
2. Add the **API Data Table** block.
3. Open the block sidebar and toggle the columns you want visible.

### Refreshing from the admin screen

1. Go to **Ziad API** in the admin sidebar.
2. Review the current dataset.
3. Click **Refresh Data** to bypass the cache and re-fetch.

### Refreshing from the command line

```bash
wp ziad-api refresh
```

This bypasses the transient and reports how many rows were retrieved. It is the
right hook for a post-deployment step or a cron job.

---

## Configuration

Set on activation, and adjustable through the `ziad_api_settings` option:

| Option | Default | Purpose |
|---|---|---|
| `api_endpoint` | `https://jsonplaceholder.typicode.com/posts` | The REST endpoint to fetch |
| `cache_expiry` | `3600` | Transient lifetime, in seconds |
| `enable_cache` | `true` | Whether responses are cached at all |
| `show_admin_notices` | `true` | Whether the plugin surfaces admin notices |

Uninstalling removes every option and transient the plugin created.

---

## Project layout

```text
ziad-api-plugin/
├── ziad-api-plugin.php        # Plugin header, constants, lifecycle hooks
├── src/
│   ├── Plugin.php             # Singleton that wires the components together
│   ├── APIClient.php          # Outbound requests and transient caching
│   ├── AJAXHandler.php        # Front-end AJAX endpoint
│   ├── AdminPage.php          # Admin screen
│   ├── Blocks/
│   │   ├── BlockRegistrar.php # Block registration
│   │   └── assets/            # block.js, frontend.js, block.css
│   └── CLI/
│       └── RefreshDataCommand.php
├── assets/                    # Admin screen CSS and JS
├── bin/check-psr4.php         # Guards namespace/directory casing
├── composer.json
└── package.json
```

---

## A note on the directory casing check

`bin/check-psr4.php` exists because PSR-4 resolution is case-sensitive but most
development machines are not. A class in `src/Cli/` declaring
`namespace Ziad\APIPlugin\CLI` loads perfectly on Windows and macOS, then fails
silently on the Linux host it is deployed to — and because the CLI registration
is wrapped in a `class_exists()` guard, the failure is invisible rather than
loud. The check runs in CI so that mismatch cannot reach a release again.

---

## License

Released under the [MIT License](LICENSE).

MIT is GPL-compatible, so the plugin remains distributable alongside WordPress
itself. If you plan to submit it to the WordPress.org plugin directory, switch
the header, `composer.json` and this file to GPL-2.0-or-later, which is the
convention there.
