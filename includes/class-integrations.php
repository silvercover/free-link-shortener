<?php

/**
 * Editor metabox and post/product list row actions.
 *
 * @package FreeLinkShortener
 */

namespace FreeLinkShortener;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Adds quick-shorten controls inside the editor and the posts list.
 */
class Integrations
{

    /**
     * Data layer instance.
     *
     * @var Links
     */
    private $links;

    /**
     * Post types that get the metabox / row action.
     *
     * @var array
     */
    private $post_types = array('post', 'page', 'product');

    /**
     * Constructor.
     *
     * @param Links $links Data layer.
     */
    public function __construct(Links $links)
    {
        $this->links = $links;
    }

    /**
     * Hook into WordPress admin.
     *
     * @return void
     */
    public function register()
    {
        add_action('add_meta_boxes', array($this, 'add_metabox'));

        // Row actions for posts/products and pages.
        add_filter('post_row_actions', array($this, 'row_action'), 10, 2);
        add_filter('page_row_actions', array($this, 'row_action'), 10, 2);

        // AJAX (metabox) and redirect (row action) handlers.
        add_action('wp_ajax_fls_create_for_post', array($this, 'ajax_create_for_post'));
        add_action('admin_post_fls_shorten_post', array($this, 'handle_shorten_post'));
    }

    /**
     * Register the metabox on supported post types.
     *
     * @return void
     */
    public function add_metabox()
    {
        foreach ($this->post_types as $type) {
            add_meta_box(
                'fls_metabox',
                __('Short Link', 'free-link-shortener'),
                array($this, 'render_metabox'),
                $type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render the metabox content.
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function render_metabox($post)
    {
        $link = $this->links->get_by_post($post->ID);
        echo '<div id="fls-metabox-result">';
        if ($link) {
            echo wp_kses_post($this->existing_link_markup($link));
        } else {
            printf(
                '<p>%s</p><button type="button" class="button button-primary" id="fls-create-for-post" data-post="%d">%s</button>',
                esc_html__('No short link yet for this content.', 'free-link-shortener'),
                esc_attr($post->ID),
                esc_html__('Create Short Link', 'free-link-shortener')
            );
        }
        echo '</div>';
    }

    /**
     * Build the HTML shown when a short link already exists.
     *
     * @param object $link Link row.
     * @return string
     */
    private function existing_link_markup($link)
    {
        $short_url = $this->links->short_url($link->slug);
        $report    = admin_url('admin.php?page=free-link-shortener&view=' . $link->id);

        ob_start();
?>
        <p><strong><?php esc_html_e('Short link:', 'free-link-shortener'); ?></strong></p>
        <p>
            <input type="text" readonly class="widefat" value="<?php echo esc_attr($short_url); ?>"
                onclick="this.select();" style="margin-bottom:6px;">
        </p>
        <button type="button" class="button fls-copy-btn"
            data-url="<?php echo esc_attr($short_url); ?>"
            data-copied="<?php esc_attr_e('Copied!', 'free-link-shortener'); ?>">
            <?php esc_html_e('Copy', 'free-link-shortener'); ?>
        </button>
        <a href="<?php echo esc_url($report); ?>" class="button"><?php esc_html_e('Report', 'free-link-shortener'); ?></a>
<?php
        return ob_get_clean();
    }

    /**
     * Add a row action under each supported post in the list table.
     *
     * @param array    $actions Existing actions.
     * @param \WP_Post $post    Post object.
     * @return array
     */
    public function row_action($actions, $post)
    {
        if (! in_array($post->post_type, $this->post_types, true) || ! current_user_can('manage_options')) {
            return $actions;
        }

        $link = $this->links->get_by_post($post->ID);
        if ($link) {
            $short_url = $this->links->short_url($link->slug);
            $actions['fls_short'] = sprintf(
                '<a href="#" class="fls-copy-btn" data-url="%s" data-copied="%s">%s</a>',
                esc_attr($short_url),
                esc_attr__('Copied!', 'free-link-shortener'),
                esc_html__('Copy short link', 'free-link-shortener')
            );
        } else {
            // AJAX shorten link (no page refresh).
            $actions['fls_short'] = sprintf(
                '<a href="#" class="fls-shorten-btn" data-post="%d" data-copy-label="%s" data-copied="%s">%s</a>',
                absint($post->ID),
                esc_attr__('Copy short link', 'free-link-shortener'),
                esc_attr__('Copied!', 'free-link-shortener'),
                esc_html__('Shorten link', 'free-link-shortener')
            );
        }

        return $actions;
    }

    /**
     * AJAX handler: create a short link for a post (metabox + row action).
     *
     * @return void
     */
    public function ajax_create_for_post()
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Access denied.', 'free-link-shortener')));
        }
        check_ajax_referer('fls_ajax', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $link    = $this->links->create_for_post($post_id);

        if (! $link) {
            wp_send_json_error(array('message' => __('Could not create the link.', 'free-link-shortener')));
        }

        wp_send_json_success(
            array(
                'html'      => $this->existing_link_markup($link), // For the metabox.
                'short_url' => $this->links->short_url($link->slug), // For the row action.
            )
        );
    }


    /**
     * Row action handler: create a short link, then redirect back.
     *
     * @return void
     */
    public function handle_shorten_post()
    {
        $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;

        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied.', 'free-link-shortener'));
        }
        check_admin_referer('fls_shorten_post_' . $post_id);

        $this->links->create_for_post($post_id);

        $back = wp_get_referer() ? wp_get_referer() : admin_url();
        wp_safe_redirect($back);
        exit;
    }
}
