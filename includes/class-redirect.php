<?php
/**
 * Front-end redirect handling.
 *
 * @package FreeLinkShortener
 */

namespace FreeLinkShortener;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the rewrite rule and performs the redirect + click logging.
 */
class Redirect {

    /**
     * Data layer instance.
     *
     * @var Links
     */
    private $links;

    /**
     * Constructor.
     *
     * @param Links $links Data layer.
     */
    public function __construct( Links $links ) {
        $this->links = $links;
    }

    /**
     * Hook into WordPress.
     *
     * @return void
     */
    public function register() {
        add_action( 'init', array( __CLASS__, 'register_rewrite_rule' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_redirect' ) );
    }

    /**
     * Register the rewrite rule for /go/code.
     *
     * @return void
     */
    public static function register_rewrite_rule() {
        add_rewrite_rule(
            '^' . FLS_SLUG_BASE . '/([^/]+)/?$',
            'index.php?fls_slug=$matches[1]',
            'top'
        );
    }

    /**
     * Register the custom query variable.
     *
     * @param array $vars Existing query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'fls_slug';
        return $vars;
    }

    /**
     * Resolve the slug, record the click and redirect.
     *
     * @return void
     */
    public function handle_redirect() {
        $slug = get_query_var( 'fls_slug' );
        if ( empty( $slug ) ) {
            return;
        }

        $link = $this->links->get_by_slug( $slug );
        if ( ! $link ) {
            status_header( 404 );
            return;
        }

        $this->links->record_click( $link->id );

        wp_redirect( esc_url_raw( $link->target_url ), 301 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
        exit;
    }
}
