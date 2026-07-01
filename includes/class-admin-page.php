<?php
/**
 * Admin dashboard page and click reports.
 *
 * @package FreeLinkShortener
 */

namespace FreeLinkShortener;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the management page and handles create/delete form submissions.
 */
class Admin_Page {

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
     * Hook into WordPress admin.
     *
     * @return void
     */
    public function register() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_post_fls_create_link', array( $this, 'handle_create' ) );
        add_action( 'admin_post_fls_delete_link', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_fls_update_link', array( $this, 'handle_update' ) );
    }

    /**
     * Register the top-level admin menu page (admins only).
     *
     * @return void
     */
    public function add_menu() {
        add_menu_page(
            __( 'Free Link Shortener', 'free-link-shortener' ),
            __( 'Link Shortener', 'free-link-shortener' ),
            'manage_options',
            'free-link-shortener',
            array( $this, 'render' ),
            'dashicons-admin-links',
            80
        );
    }

	/**
	 * Render pagination links below the table.
	 *
	 * @param int $total    Total number of links.
	 * @param int $per_page Items per page.
	 * @param int $paged    Current page number.
	 * @return void
	 */
	private function render_pagination( $total, $per_page, $paged ) {
		$total_pages = (int) ceil( $total / $per_page );
		if ( $total_pages <= 1 ) {
			return;
		}

		$base_url = admin_url( 'admin.php?page=free-link-shortener' );

		$links = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%', $base_url ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $total_pages,
				'prev_text' => __( '&laquo; Previous', 'free-link-shortener' ),
				'next_text' => __( 'Next &raquo;', 'free-link-shortener' ),
			)
		);

		if ( $links ) {
			echo '<div class="tablenav bottom"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
		}
	}

    /**
     * Handle the "create link" form submission.
     *
     * @return void
     */
    public function handle_create() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'free-link-shortener' ) );
        }
        check_admin_referer( 'fls_create_link' );

        $target = isset( $_POST['target_url'] ) ? wp_unslash( $_POST['target_url'] ) : '';
        $slug   = isset( $_POST['custom_slug'] ) ? wp_unslash( $_POST['custom_slug'] ) : '';

        $result = $this->links->create( $target, $slug );
        $msg    = ! empty( $result['success'] ) ? 'created' : $result['error'];

        wp_safe_redirect( admin_url( 'admin.php?page=free-link-shortener&msg=' . $msg ) );
        exit;
    }
	
	/**
	 * Handle the "update link" form submission.
	 *
	 * @return void
	 */
	public function handle_update() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'free-link-shortener' ) );
		}
		check_admin_referer( 'fls_update_link' );

		$id     = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
		$target = isset( $_POST['target_url'] ) ? wp_unslash( $_POST['target_url'] ) : '';
		$slug   = isset( $_POST['custom_slug'] ) ? wp_unslash( $_POST['custom_slug'] ) : '';

		$result = $this->links->update( $id, $target, $slug );

		if ( ! empty( $result['success'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=free-link-shortener&msg=updated' ) );
		} else {
			// Return to the edit form with the relevant error.
			wp_safe_redirect( admin_url( 'admin.php?page=free-link-shortener&edit=' . $id . '&msg=' . $result['error'] ) );
		}
		exit;
	}
	

    /**
     * Handle the "delete link" action.
     *
     * @return void
     */
    public function handle_delete() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'free-link-shortener' ) );
        }
        check_admin_referer( 'fls_delete_link' );

        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $this->links->delete( $id );

        wp_safe_redirect( admin_url( 'admin.php?page=free-link-shortener&msg=deleted' ) );
        exit;
    }

    /**
     * Render the admin page (list/create or single report).
     *
     * @return void
     */
    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Single link report.
        if ( isset( $_GET['view'] ) ) {
            $this->render_report( absint( $_GET['view'] ) );
            return;
        }

		// Edit link form.
		if ( isset( $_GET['edit'] ) ) {
			$this->render_edit_form( absint( $_GET['edit'] ) );
			return;
		}

		$this->render_notice();

		// Pagination setup.
		$per_page = 20;
		$total    = $this->links->count_all();
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$links    = $this->links->get_paged_with_counts( $per_page, $paged );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free Link Shortener', 'free-link-shortener' ); ?></h1>

			<h2><?php esc_html_e( 'Create a New Link', 'free-link-shortener' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="fls_create_link">
				<?php wp_nonce_field( 'fls_create_link' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="target_url"><?php esc_html_e( 'Target URL', 'free-link-shortener' ); ?></label></th>
						<td><input type="url" name="target_url" id="target_url" class="regular-text" placeholder="https://example.com/page" required></td>
					</tr>
					<tr>
						<th><label for="custom_slug"><?php esc_html_e( 'Custom slug (optional)', 'free-link-shortener' ); ?></label></th>
						<td>
							<code><?php echo esc_html( home_url( '/' . FLS_SLUG_BASE . '/' ) ); ?></code>
							<input type="text" name="custom_slug" id="custom_slug" class="regular-text"
								   placeholder="<?php esc_attr_e( 'Leave empty to auto-generate', 'free-link-shortener' ); ?>">
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Create Short Link', 'free-link-shortener' ) ); ?>
			</form>

			<hr>
			<h2>
				<?php esc_html_e( 'Created Links', 'free-link-shortener' ); ?>
				<span class="count">(<?php echo esc_html( number_format_i18n( $total ) ); ?>)</span>
			</h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Short Link', 'free-link-shortener' ); ?></th>
						<th><?php esc_html_e( 'Copy', 'free-link-shortener' ); ?></th>
						<th><?php esc_html_e( 'Target', 'free-link-shortener' ); ?></th>
						<th><?php esc_html_e( 'Clicks', 'free-link-shortener' ); ?></th>
						<th><?php esc_html_e( 'Created', 'free-link-shortener' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'free-link-shortener' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( $links ) : foreach ( $links as $link ) :
					$short_url = $this->links->short_url( $link->slug ); ?>
					<tr>
						<td><a href="<?php echo esc_url( $short_url ); ?>" target="_blank"><?php echo esc_html( $short_url ); ?></a></td>
						<td>
							<button type="button" class="button button-small fls-copy-btn"
									data-url="<?php echo esc_attr( $short_url ); ?>"
									data-copied="<?php esc_attr_e( 'Copied!', 'free-link-shortener' ); ?>">
								<?php esc_html_e( 'Copy', 'free-link-shortener' ); ?>
							</button>
						</td>
						<td><a href="<?php echo esc_url( $link->target_url ); ?>" target="_blank"><?php echo esc_html( wp_trim_words( $link->target_url, 8, '...' ) ); ?></a></td>
						<td><strong><?php echo intval( $link->clicks ); ?></strong></td>
						<td><?php echo esc_html( $link->created_at ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-link-shortener&edit=' . $link->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'free-link-shortener' ); ?></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-link-shortener&view=' . $link->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Report', 'free-link-shortener' ); ?></a>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=fls_delete_link&id=' . $link->id ), 'fls_delete_link' ) ); ?>"
							   class="button button-small"
							   onclick="return confirm('<?php echo esc_js( __( 'Delete this link?', 'free-link-shortener' ) ); ?>');"><?php esc_html_e( 'Delete', 'free-link-shortener' ); ?></a>
						</td>
					</tr>
				<?php endforeach; else : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No links created yet.', 'free-link-shortener' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>

			<?php $this->render_pagination( $total, $per_page, $paged ); ?>
		</div>
		<?php

    }

    /**
     * Render an admin notice based on the "msg" query arg.
     *
     * @return void
     */
    private function render_notice() {
        if ( ! isset( $_GET['msg'] ) ) {
            return;
        }
		$messages = array(
			'created'   => __( 'Link created successfully.', 'free-link-shortener' ),
			'updated'   => __( 'Link updated successfully.', 'free-link-shortener' ),
			'deleted'   => __( 'Link deleted.', 'free-link-shortener' ),
			'duplicate' => __( 'This slug is already in use. Please choose another one.', 'free-link-shortener' ),
			'empty'     => __( 'Please enter a target URL and slug.', 'free-link-shortener' ),
			'not_found' => __( 'Link not found.', 'free-link-shortener' ),
		);
        $key  = sanitize_key( wp_unslash( $_GET['msg'] ) );
        $type = in_array( $key, array( 'duplicate', 'empty' ), true ) ? 'error' : 'success';
        if ( isset( $messages[ $key ] ) ) {
            echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>'
                . esc_html( $messages[ $key ] ) . '</p></div>';
        }
    }

	/**
	 * Render the edit form for a single link.
	 *
	 * @param int $link_id Link ID.
	 * @return void
	 */
	private function render_edit_form( $link_id ) {
		$link = $this->links->get( $link_id );
		if ( ! $link ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Link not found.', 'free-link-shortener' ) . '</p></div>';
			return;
		}

		$this->render_notice();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Edit Link', 'free-link-shortener' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="fls_update_link">
				<input type="hidden" name="link_id" value="<?php echo esc_attr( $link->id ); ?>">
				<?php wp_nonce_field( 'fls_update_link' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="target_url"><?php esc_html_e( 'Target URL', 'free-link-shortener' ); ?></label></th>
						<td><input type="url" name="target_url" id="target_url" class="regular-text"
								   value="<?php echo esc_attr( $link->target_url ); ?>" required></td>
					</tr>
					<tr>
						<th><label for="custom_slug"><?php esc_html_e( 'Slug', 'free-link-shortener' ); ?></label></th>
						<td>
							<code><?php echo esc_html( home_url( '/' . FLS_SLUG_BASE . '/' ) ); ?></code>
							<input type="text" name="custom_slug" id="custom_slug" class="regular-text"
								   value="<?php echo esc_attr( $link->slug ); ?>" required>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Update Link', 'free-link-shortener' ) ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=free-link-shortener' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'free-link-shortener' ); ?></a>
			</form>
		</div>
		<?php
	}

    /**
     * Render the click report for a single link.
     *
     * @param int $link_id Link ID.
     * @return void
     */
    private function render_report( $link_id ) {
        $link = $this->links->get( $link_id );
        if ( ! $link ) {
            echo '<div class="wrap"><p>' . esc_html__( 'Link not found.', 'free-link-shortener' ) . '</p></div>';
            return;
        }

        $clicks    = $this->links->get_clicks( $link_id );
        $short_url = $this->links->short_url( $link->slug );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Click Report', 'free-link-shortener' ); ?></h1>
            <p>
                <?php esc_html_e( 'Short link:', 'free-link-shortener' ); ?>
                <a href="<?php echo esc_url( $short_url ); ?>" target="_blank"><?php echo esc_html( $short_url ); ?></a>
            </p>
            <p>
                <?php
                /* translators: %s: total number of clicks. */
                printf( esc_html__( 'Total clicks: %s', 'free-link-shortener' ), '<strong>' . count( $clicks ) . '</strong>' );
                ?>
            </p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=free-link-shortener' ) ); ?>" class="button"><?php esc_html_e( 'Back', 'free-link-shortener' ); ?></a>

            <table class="wp-list-table widefat fixed striped" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date / Time', 'free-link-shortener' ); ?></th>
                        <th><?php esc_html_e( 'IP Address', 'free-link-shortener' ); ?></th>
                        <th><?php esc_html_e( 'Country', 'free-link-shortener' ); ?></th>
                        <th><?php esc_html_e( 'Browser', 'free-link-shortener' ); ?></th>
                        <th><?php esc_html_e( 'Referrer', 'free-link-shortener' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( $clicks ) : foreach ( $clicks as $c ) : ?>
                    <tr>
                        <td><?php echo esc_html( $c->click_time ); ?></td>
                        <td><?php echo esc_html( $c->ip_address ); ?></td>
                        <td><?php echo esc_html( $c->country ); ?></td>
                        <td><?php echo esc_html( $c->browser ); ?></td>
                        <td><?php echo $c->referrer ? esc_html( $c->referrer ) : '<em>' . esc_html__( 'Direct', 'free-link-shortener' ) . '</em>'; ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No clicks recorded yet.', 'free-link-shortener' ); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
