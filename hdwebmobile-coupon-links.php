<?php

/**
 * Plugin Name: HDWebmobile Coupon Links & Conditions
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-coupon-links/
 * Description: Cart-condition coupons and apply-by-link URLs. A coupon link only ever applies an existing published coupon by exact code, and coupon conditions are edited only on the coupon screen.
 * Version: 1.0.0
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-coupon-links
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdcl;

if (!defined('ABSPATH')) {
    exit;
}

define('HDCL_VERSION', '1.0.0');
define('HDCL_PLUGIN_FILE', __FILE__);
define('HDCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDCL_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDCL_PLUGIN_DIR . 'includes/class-hdcl-activator.php';

register_activation_hook(__FILE__, array(HDCL_Activator::class, 'activate'));

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDCL_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    require_once HDCL_PLUGIN_DIR . 'includes/class-hdcl-core.php';
    HDCL_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'hdwebmobile-coupon-links') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
