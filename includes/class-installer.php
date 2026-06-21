<?php
/**
 * Installer: creates database tables on activation.
 *
 * @package FreeLinkShortener
 */

namespace FreeLinkShortener;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles plugin activation tasks.
 */
class Installer {

    /**
     * Create tables and flush rewrite rules.
     *
     * @return void
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $links_table     = $wpdb->prefix . 'fls_links';
        $clicks_table    = $wpdb->prefix . 'fls_clicks';

        // Links table (post_id links a short URL to a post/product).
        $sql1 = "CREATE TABLE {$links_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(191) NOT NULL,
            target_url TEXT NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE KEY slug (slug),
            KEY post_id (post_id),
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Clicks table.
        $sql2 = "CREATE TABLE {$clicks_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            link_id BIGINT(20) UNSIGNED NOT NULL,
            click_time DATETIME NOT NULL,
            ip_address VARCHAR(45),
            country VARCHAR(100),
            browser VARCHAR(191),
            referrer TEXT,
            PRIMARY KEY (id),
            KEY link_id (link_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql1 );
        dbDelta( $sql2 );

        // Register the rewrite rule, then flush so short links work immediately.
        Redirect::register_rewrite_rule();
        flush_rewrite_rules();
    }
}
