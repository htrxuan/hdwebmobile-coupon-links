<?php

namespace htrxuan\hdcl;

if (!defined('ABSPATH')) {
    exit;
}

final class HDCL_Core
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
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDCL_PLUGIN_DIR . 'includes/class-hdcl-conditions.php';
        require_once HDCL_PLUGIN_DIR . 'includes/class-hdcl-link.php';
        require_once HDCL_PLUGIN_DIR . 'includes/class-hdcl-admin.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        HDCL_Conditions::get_instance();
        HDCL_Link::get_instance();
        HDCL_Admin::get_instance();
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdcl_wc_missing_notice')) {
            return;
        }
        delete_transient('hdcl_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Coupon Links & Conditions requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-coupon-links'); ?>
            </p>
        </div>
        <?php
    }
}
