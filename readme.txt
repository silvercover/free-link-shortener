=== Free Link Shortener ===
Contributors: silvercover
Donate link: https://silvercover.ir
Tags: link shortener, url shortener, short link, click tracking, woocommerce
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin that creates branded short links on your own domain, counts clicks, and gives you detailed reports — no third-party service required.

== Description ==

**Free Link Shortener** turns long URLs into clean, branded short links served from your own WordPress site (e.g. `https://yoursite.com/go/abc123`). It is ideal for sharing posts and WooCommerce products, tracking campaign performance, and keeping all your click data in your own database instead of an external shortener.

= Features =

* Branded short links on your own domain (`/go/your-code`).
* Automatic or custom slugs — let the plugin generate a random code, or type your own.
* Detailed click tracking — date/time, IP address, country, browser, and referrer.
* Per-link reports inside a dedicated admin dashboard.
* Editor metabox — create and copy a short link directly from the post/page/product edit screen.
* AJAX row actions — shorten or copy a link straight from the Posts/Products list, with no page reload.
* One-click copy to clipboard (with a fallback for non-HTTPS environments).
* Translation ready (i18n) — full text domain support with an included `.pot` file.
* Secure — nonce-protected forms and `manage_options` capability checks (admins only).

= Requirements =

* WordPress 5.6 or higher
* PHP 7.4 or higher
* (Optional) WooCommerce — for short links on product pages

= Privacy & External Services =

Country detection uses the free [ip-api.com](https://ip-api.com) service. Visitor IP addresses are sent to this service to resolve the country name. The free tier is limited to 45 requests per minute and is intended for non-commercial use. If you prefer not to use it, you can disable or replace the country lookup in `includes/class-links.php`.

== Installation ==

1. Upload the `free-link-shortener` folder to the `/wp-content/plugins/` directory, or install the plugin directly through the WordPress plugins screen.
2. Activate **Free Link Shortener** through the 'Plugins' screen in WordPress.
3. Go to **Settings → Permalinks** and click **Save Changes** once to flush rewrite rules (required for `/go/...` links to work).

= Upgrading =

If you upgrade from an older version, deactivate and reactivate the plugin once so the database schema updates safely.

== Frequently Asked Questions ==

= How do I create a short link manually? =

Go to **Link Shortener** in the admin menu, enter a target URL, optionally set a custom slug, and click **Create Short Link**.

= How do I shorten a link for a post or product? =

From the editor, use the **Short Link** metabox in the sidebar to generate and copy a link instantly. From the Posts/Products list, hover over any row and click **Shorten link** (AJAX, no reload). Once created, it becomes a **Copy short link** action.

= How do I view click reports? =

On the Link Shortener page, click **Report** next to any link to see every recorded click with its details.

= Can I change the `/go/` base path? =

Yes. Edit the following constant in the main plugin file and re-save your permalinks:

`define( 'FLS_SLUG_BASE', 'go' ); // e.g. change 'go' to 'link' or 's'`

= Does this plugin send any data to third parties? =

Visitor IP addresses are sent to the free ip-api.com service for country lookup. You can disable or replace this in `includes/class-links.php` if you prefer not to use it.

= Is the plugin translatable? =

Yes, it is fully translatable. A `free-link-shortener.pot` template is included in the `languages/` folder. To regenerate it with WP-CLI:

`wp i18n make-pot . languages/free-link-shortener.pot`

== Screenshots ==

1. Link Shortener admin dashboard with the list of created links.
2. Per-link click report showing date, IP, country, browser, and referrer.
3. Short Link metabox in the post/page/product editor sidebar.

== Changelog ==

= 1.1.0 =
* Added AJAX row actions to shorten and copy links from the Posts/Products list.
* Added editor metabox for quick short link creation and copying.
* Improved click reports UI.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Deactivate and reactivate the plugin after upgrading to safely update the database schema.
