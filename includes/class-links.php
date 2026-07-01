<?php
/**
 * Data layer: links CRUD, slug generation and click logging.
 *
 * @package FreeLinkShortener
 */

namespace FreeLinkShortener;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles all database read/write operations for links and clicks.
 */
class Links {

    /**
     * Links table name.
     *
     * @var string
     */
    private $links_table;

    /**
     * Clicks table name.
     *
     * @var string
     */
    private $clicks_table;

    /**
     * Constructor: resolve table names.
     */
    public function __construct() {
        global $wpdb;
        $this->links_table  = $wpdb->prefix . 'fls_links';
        $this->clicks_table = $wpdb->prefix . 'fls_clicks';
    }

    /**
     * Get the links table name.
     *
     * @return string
     */
    public function links_table() {
        return $this->links_table;
    }

    /**
     * Get the clicks table name.
     *
     * @return string
     */
    public function clicks_table() {
        return $this->clicks_table;
    }

    /**
     * Build the full short URL for a given slug.
     *
     * @param string $slug Link slug.
     * @return string
     */
    public function short_url( $slug ) {
        return home_url( '/' . FLS_SLUG_BASE . '/' . $slug );
    }

    /**
     * Find a link row by its slug.
     *
     * @param string $slug Link slug.
     * @return object|null
     */
    public function get_by_slug( $slug ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->links_table} WHERE slug = %s", $slug )
        );
    }

    /**
     * Find a link row by its ID.
     *
     * @param int $id Link ID.
     * @return object|null
     */
	public function get( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM %i WHERE id = %d", $this->links_table, absint( $id ) )
		);
	}

    /**
     * Find the link attached to a given post/product.
     *
     * @param int $post_id Post ID.
     * @return object|null
     */
	public function get_by_post( $post_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM %i WHERE post_id = %d", $this->links_table, absint( $post_id ) )
		);
	}

    /**
     * Check whether a slug already exists.
     *
     * @param string $slug Link slug.
     * @return bool
     */
    public function slug_exists( $slug ) {
        global $wpdb;
        return (bool) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$this->links_table} WHERE slug = %s", $slug )
        );
    }

    /**
     * Insert a new link.
     *
     * @param string $target_url Destination URL.
     * @param string $slug       Slug (empty = auto-generate).
     * @param int    $post_id    Optional related post ID.
     * @return array {
     *     @type bool   $success Whether the link was created.
     *     @type string $error   Error code on failure ('empty'|'duplicate').
     *     @type object $link    The created link row on success.
     * }
     */
    public function create( $target_url, $slug = '', $post_id = 0 ) {
        global $wpdb;

        $target_url = esc_url_raw( $target_url );
        if ( empty( $target_url ) ) {
            return array( 'success' => false, 'error' => 'empty' );
        }

        $slug = sanitize_title( $slug );
        if ( empty( $slug ) ) {
            $slug = $this->generate_unique_slug();
        } elseif ( $this->slug_exists( $slug ) ) {
            return array( 'success' => false, 'error' => 'duplicate' );
        }

        $wpdb->insert(
            $this->links_table,
            array(
                'slug'       => $slug,
                'target_url' => $target_url,
                'post_id'    => absint( $post_id ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%s' )
        );

        return array(
            'success' => true,
            'link'    => $this->get( $wpdb->insert_id ),
        );
    }

    /**
     * Create (or return the existing) short link for a post/product.
     *
     * @param int $post_id Post ID.
     * @return object|null The link row, or null on failure.
     */
    public function create_for_post( $post_id ) {
        $post_id  = absint( $post_id );
        $existing = $this->get_by_post( $post_id );
        if ( $existing ) {
            return $existing;
        }

        $result = $this->create( get_permalink( $post_id ), '', $post_id );
        return ! empty( $result['success'] ) ? $result['link'] : null;
    }

	/**
	 * Update an existing link's target URL and/or slug.
	 *
	 * @param int    $id         Link ID.
	 * @param string $target_url New destination URL.
	 * @param string $slug       New slug.
	 * @return array {
	 *     @type bool   $success Whether the update succeeded.
	 *     @type string $error   Error code on failure ('empty'|'duplicate'|'not_found').
	 * }
	 */
	public function update( $id, $target_url, $slug ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $this->get( $id ) ) {
			return array( 'success' => false, 'error' => 'not_found' );
		}

		$target_url = esc_url_raw( $target_url );
		if ( empty( $target_url ) ) {
			return array( 'success' => false, 'error' => 'empty' );
		}

		$slug = sanitize_title( $slug );
		if ( empty( $slug ) ) {
			return array( 'success' => false, 'error' => 'empty' );
		}

		// Ensure the slug is not used by a different link.
		$owner = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$this->links_table} WHERE slug = %s", $slug )
		);
		if ( $owner && absint( $owner ) !== $id ) {
			return array( 'success' => false, 'error' => 'duplicate' );
		}

		$wpdb->update(
			$this->links_table,
			array(
				'slug'       => $slug,
				'target_url' => $target_url,
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return array( 'success' => true );
	}

    /**
     * Delete a link and all of its recorded clicks.
     *
     * @param int $id Link ID.
     * @return void
     */
    public function delete( $id ) {
        global $wpdb;
        $id = absint( $id );
        $wpdb->delete( $this->links_table, array( 'id' => $id ), array( '%d' ) );
        $wpdb->delete( $this->clicks_table, array( 'link_id' => $id ), array( '%d' ) );
    }

	/**
	 * Get a paginated set of links together with their click counts.
	 *
	 * @param int $per_page Number of links per page.
	 * @param int $page     Current page number (1-based).
	 * @return array
	 */
	public function get_paged_with_counts( $per_page = 20, $page = 1 ) {
		global $wpdb;

		$per_page = max( 1, absint( $per_page ) );
		$page     = max( 1, absint( $page ) );
		$offset   = ( $page - 1 ) * $per_page;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*, (SELECT COUNT(*) FROM {$this->clicks_table} c WHERE c.link_id = l.id) AS clicks
				 FROM {$this->links_table} l
				 ORDER BY l.created_at DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
	}

	/**
	 * Get the total number of links.
	 *
	 * @return int
	 */
	public function count_all() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->links_table}" );
	}


    /**
     * Get all clicks for a given link.
     *
     * @param int $link_id Link ID.
     * @return array
     */
    public function get_clicks( $link_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->clicks_table} WHERE link_id = %d ORDER BY click_time DESC",
                absint( $link_id )
            )
        );
    }

    /**
     * Record a click for a link, capturing IP, country, browser and referrer.
     *
     * @param int $link_id Link ID.
     * @return void
     */
    public function record_click( $link_id ) {
        global $wpdb;

        $ip       = $this->get_user_ip();
        $referrer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
        $agent    = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

        $wpdb->insert(
            $this->clicks_table,
            array(
                'link_id'    => absint( $link_id ),
                'click_time' => current_time( 'mysql' ),
                'ip_address' => $ip,
                'country'    => $this->get_country_from_ip( $ip ),
                'browser'    => $this->detect_browser( $agent ),
                'referrer'   => $referrer,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Generate a unique random slug.
     *
     * @param int $length Slug length.
     * @return string
     */
    private function generate_unique_slug( $length = 6 ) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $slug = '';
            for ( $i = 0; $i < $length; $i++ ) {
                $slug .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
            }
        } while ( $this->slug_exists( $slug ) );
        return $slug;
    }

    /**
     * Get the visitor's real IP address.
     *
     * @return string
     */
    private function get_user_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $forwarded = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $ip        = $forwarded[0];
        } else {
            $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
        }
        return sanitize_text_field( trim( $ip ) );
    }

    /**
     * Resolve a country name from an IP using the free ip-api service.
     *
     * @param string $ip IP address.
     * @return string
     */
    private function get_country_from_ip( $ip ) {
        if ( empty( $ip ) || '127.0.0.1' === $ip ) {
            return __( 'Local', 'free-link-shortener' );
        }
        $response = wp_remote_get(
            "http://ip-api.com/json/{$ip}?fields=country",
            array( 'timeout' => 3 )
        );
        if ( is_wp_error( $response ) ) {
            return __( 'Unknown', 'free-link-shortener' );
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset( $body['country'] )
            ? sanitize_text_field( $body['country'] )
            : __( 'Unknown', 'free-link-shortener' );
    }

    /**
     * Detect the browser name from a User-Agent string.
     *
     * @param string $agent User-Agent string.
     * @return string
     */
    private function detect_browser( $agent ) {
        $agent = strtolower( $agent );
        if ( false !== strpos( $agent, 'edg' ) ) {
            return 'Edge';
        }
        if ( false !== strpos( $agent, 'opr' ) || false !== strpos( $agent, 'opera' ) ) {
            return 'Opera';
        }
        if ( false !== strpos( $agent, 'chrome' ) ) {
            return 'Chrome';
        }
        if ( false !== strpos( $agent, 'safari' ) ) {
            return 'Safari';
        }
        if ( false !== strpos( $agent, 'firefox' ) ) {
            return 'Firefox';
        }
        if ( false !== strpos( $agent, 'msie' ) || false !== strpos( $agent, 'trident' ) ) {
            return 'Internet Explorer';
        }
        return __( 'Other', 'free-link-shortener' );
    }
}
