# Free Link Shortener

A lightweight WordPress plugin that creates branded short links on your **own domain**, counts clicks, and gives you detailed reports — no third-party service required.

![WordPress](https://img.shields.io/badge/WordPress-Plugin-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/License-GPLv2%2B-green)
![Version](https://img.shields.io/badge/Version-1.1.0-orange)

---

## Overview

**Free Link Shortener** turns long URLs into clean, branded short links served from your own WordPress site (e.g. `https://yoursite.com/go/abc123`). It is ideal for sharing posts and WooCommerce products, tracking campaign performance, and keeping all your click data in your own database instead of an external shortener.

## Features

- **Branded short links** on your own domain (`/go/your-code`).
- **Automatic or custom slugs** — let the plugin generate a random code, or type your own.
- **Detailed click tracking** — date/time, IP address, country, browser, and referrer.
- **Per-link reports** inside a dedicated admin dashboard.
- **Editor metabox** — create and copy a short link directly from the post/page/product edit screen.
- **AJAX row actions** — shorten or copy a link straight from the Posts/Products list, with no page reload.
- **One-click copy to clipboard** (with a fallback for non-HTTPS environments).
- **Translation ready (i18n)** — full text domain support with an included `.pot` file.
- **Secure** — nonce-protected forms and `manage_options` capability checks (admins only).

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- (Optional) WooCommerce — for short links on product pages

## Installation

1. Download or clone this repository into your plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/your-username/free-link-shortener.git
   ```
2. Activate **Free Link Shortener** from **Plugins** in your WordPress dashboard.
3. Go to **Settings → Permalinks** and click **Save Changes** once to flush rewrite rules (required for `/go/...` links to work).

> **Note:** If you upgrade from an older version, deactivate and reactivate the plugin once so the database schema updates safely.

## Usage

### Create a link manually
Go to **Link Shortener** in the admin menu, enter a target URL, optionally set a custom slug, and click **Create Short Link**.

### Shorten a post or product
- **From the editor:** Use the **Short Link** metabox in the sidebar to generate and copy a link instantly.
- **From the list:** Hover over any post/product row and click **Shorten link** (AJAX, no reload). Once created, it becomes a **Copy short link** action.

### View reports
On the Link Shortener page, click **Report** next to any link to see every recorded click with its details.

## Short Link Structure

By default, links use the `/go/` base path:

```
https://yoursite.com/go/{slug}
```

To change the base segment, edit the constant in the main plugin file and re-save your permalinks:

```php
define( 'FLS_SLUG_BASE', 'go' ); // e.g. change 'go' to 'link' or 's'
```

## Project Structure

```
free-link-shortener/
├── free-link-shortener.php       # Bootstrap / loader
├── assets/
│   └── admin.js                  # Clipboard + AJAX logic
├── languages/                    # Translation files (.pot / .po / .mo)
└── includes/
    ├── class-plugin.php          # Orchestrator + asset loading
    ├── class-installer.php       # Database tables / activation
    ├── class-links.php           # Data layer + click logging
    ├── class-redirect.php        # Rewrite rules + redirect
    ├── class-admin-page.php      # Dashboard + reports
    └── class-integrations.php    # Metabox + row actions
```

## Translations

The plugin is fully translatable. A `free-link-shortener.pot` template is included in the `languages/` folder.

To add a translation:
1. Open the `.pot` file in [Poedit](https://poedit.net/).
2. Create a new translation for your locale (e.g. `fa_IR`).
3. Save it as `free-link-shortener-{locale}.po` / `.mo` in the `languages/` folder.

To regenerate the `.pot` file with WP-CLI:

```bash
wp i18n make-pot . languages/free-link-shortener.pot
```

## Privacy & External Services

Country detection uses the free [ip-api.com](https://ip-api.com) service. Visitor IP addresses are sent to this service to resolve the country name. The free tier is limited to 45 requests per minute and is intended for non-commercial use. If you prefer not to use it, you can disable or replace the country lookup in `includes/class-links.php`.

## Roadmap

- [ ] CSV export of click reports
- [ ] Daily click charts
- [ ] Link enable/disable & expiration
- [ ] Bulk shorten action

## Contributing

Contributions are welcome! Please open an issue to discuss a feature or bug before submitting a pull request. Follow the WordPress Coding Standards for PHP and JavaScript.

## License

This plugin is licensed under the GPLv2 (or later). See the `LICENSE` file for details.

## Author

**Hamed Takmil**
🌐 [silvercover.ir](https://silvercover.ir)

---

If you find this plugin useful, please consider giving the repository a ⭐ on GitHub!
