<?php
/**
 * Plugin Name: Free Link Shortener
 * Description: Branded short links on your own domain, with click counting, detailed reports, editor metabox and quick row actions.
 * Version: 1.1.0
 * Author: Hamed Takmil
 * Author URI: https://silvercover.ir
 * Text Domain: free-link-shortener
 * Domain Path: /languages
 */

namespace FreeLinkShortener;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access.
}

// Core constants.
define( 'FLS_VERSION', '1.1.0' );
define( 'FLS_FILE', __FILE__ );
define( 'FLS_PATH', plugin_dir_path( __FILE__ ) );
define( 'FLS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Base path segment used in short links: yoursite.com/go/code
 * Change this value (and re-save Permalinks) to use another base.
 */
define( 'FLS_SLUG_BASE', 'go' );

// Load all class files.
require_once FLS_PATH . 'includes/class-links.php';
require_once FLS_PATH . 'includes/class-installer.php';
require_once FLS_PATH . 'includes/class-redirect.php';
require_once FLS_PATH . 'includes/class-admin-page.php';
require_once FLS_PATH . 'includes/class-integrations.php';
require_once FLS_PATH . 'includes/class-plugin.php';

// Activation: create database tables and flush rewrite rules.
register_activation_hook( __FILE__, array( Installer::class, 'activate' ) );

// Bootstrap the plugin once all plugins are loaded.
add_action( 'plugins_loaded', function () {
    Plugin::instance()->run();
} );
