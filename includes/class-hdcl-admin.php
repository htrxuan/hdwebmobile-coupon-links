<?php

namespace htrxuan\hdcl;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The hub tab: a short how-to plus a link builder. It has no settings to save (there is no
 * option, no write path) -- the coupon conditions live on each coupon, and the apply-link
 * is just a URL.
 */
class HDCL_Admin
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        require_once HDCL_PLUGIN_DIR . 'includes/class-hdcl-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['coupon-links'] = array(
            'label'  => __('Coupon Links & Conditions', 'hdwebmobile-coupon-links'),
            'order'  => 43,
            'render' => array($this, 'render_page'),
        );
        return $tabs;
    }

    public function render_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'hdwebmobile-coupon-links'));
        }
        $base = home_url('/');
        ?>
        <h2><?php esc_html_e('Cart conditions', 'hdwebmobile-coupon-links'); ?></h2>
        <p><?php esc_html_e('Edit any coupon (Marketing > Coupons) and scroll to "HDWebmobile cart conditions" in the General tab. Add one or more of: a required product, a required category, a minimum item count, a minimum subtotal. The coupon is rejected with a clear message until every condition you set is met.', 'hdwebmobile-coupon-links'); ?></p>

        <h2><?php esc_html_e('Apply-by-link', 'hdwebmobile-coupon-links'); ?></h2>
        <p><?php esc_html_e('Share a link that adds a coupon to the visitor\'s cart automatically. The link only ever applies a coupon you have already created and published -- it cannot create a coupon or change a discount, and WooCommerce still runs every usage rule.', 'hdwebmobile-coupon-links'); ?></p>
        <p><?php esc_html_e('Link format -- replace SUMMER10 with your coupon code:', 'hdwebmobile-coupon-links'); ?></p>
        <p><input type="text" readonly style="width:100%;max-width:640px;" value="<?php echo esc_attr(add_query_arg('hdcl_coupon', 'SUMMER10', $base)); ?>" /></p>
        <p class="description"><?php esc_html_e('Add &hdcl_redirect=/shop/ to send the visitor to a specific page afterwards (same-site URLs only).', 'hdwebmobile-coupon-links'); ?></p>
        <?php
    }
}
