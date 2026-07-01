<?php
/**
 * Main orchestrator: wires components together and loads assets.
 *
 * @package FreeLinkShortener
 */

namespace FreeLinkShortener;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Singleton plugin bootstrapper.
 */
class Plugin {

    /**
     * Single instance.
     *
     * @var Plugin
     */
    private static $instance;

    /**
     * Data layer.
     *
     * @var Links
     */
    public $links;

    /**
     * Get the single instance.
     *
     * @return Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     */
    private function __construct() {
        $this->links = new Links();
    }

    /**
     * Register all hooks and components.
     *
     * @return void
     */
    public function run() {
        /*load_plugin_textdomain(
            'free-link-shortener',
            false,
            dirname( plugin_basename( FLS_FILE ) ) . '/languages'
        );*/

        ( new Redirect( $this->links ) )->register();

        if ( is_admin() ) {
            ( new Admin_Page( $this->links ) )->register();
            ( new Integrations( $this->links ) )->register();
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        }
    }

    /**
     * Enqueue admin JS where it is needed.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) {
        $allowed = array(
            'toplevel_page_free-link-shortener', // Plugin page.
            'post.php',                          // Edit screen.
            'post-new.php',                      // New screen.
            'edit.php',                          // List tables.
        );
        if ( ! in_array( $hook, $allowed, true ) ) {
            return;
        }

        wp_enqueue_script(
            'fls-admin',
            FLS_URL . 'assets/admin.js',
            array(),
            FLS_VERSION,
            true
        );

        wp_localize_script(
            'fls-admin',
            'FLS',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'fls_ajax' ),
                'errorText' => __( 'Something went wrong. Please try again.', 'free-link-shortener' ),
            )
        );
    }
}
