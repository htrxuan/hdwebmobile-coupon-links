<?php

namespace htrxuan\hdcl;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * "Apply this coupon by visiting a link": ?hdcl_coupon=SUMMER10 (optionally &hdcl_redirect=...).
 *
 * This is deliberately a very small, very constrained feature:
 *   - It can ONLY apply a coupon that already exists in the store and is published. The code
 *     is looked up with `new WC_Coupon($code)`; if `get_id()` is 0 or the post status is not
 *     `publish`, nothing happens. There is NO path here that creates a coupon, edits one, or
 *     sets a discount amount.
 *   - It hands the code straight to `WC()->cart->apply_coupon()`, so WooCommerce runs its
 *     own full validation (expiry, usage limits, min/max spend, allowed emails, ...) plus
 *     this plugin's cart conditions. The link can never bypass any of that.
 *   - It is rate-limited per visitor (a short transient keyed on a hash of the WC session /
 *     client) so the endpoint can't be hammered.
 *   - The optional redirect target is passed through wp_validate_redirect() with the site
 *     home as the fallback, so it can only ever bounce to a same-host URL.
 */
final class HDCL_Link
{

    const QUERY_VAR   = 'hdcl_coupon';
    const REDIRECT_VAR = 'hdcl_redirect';
    const RATE_SECONDS = 10;

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
        add_action('wp_loaded', array($this, 'maybe_apply'));
    }

    public function maybe_apply()
    {
        if (is_admin() || empty($_GET[self::QUERY_VAR])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a share link, not a form; it can only ever run WooCommerce's own apply_coupon() with an already-published code, which itself is idempotent and fully validated.
            return;
        }
        if (!function_exists('WC') || !WC()->cart) {
            return;
        }

        $code = wc_format_coupon_code(sanitize_text_field(wp_unslash($_GET[self::QUERY_VAR]))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see above.
        if ('' === $code) {
            return;
        }

        if ($this->is_rate_limited()) {
            $this->redirect();
            return;
        }
        $this->mark_rate_limited();

        // The code must resolve to a real, published coupon. Otherwise: do nothing at all.
        $coupon = new \WC_Coupon($code);
        if (!$coupon->get_id() || 'publish' !== get_post_status($coupon->get_id())) {
            $this->redirect();
            return;
        }

        if (WC()->cart->has_discount($code)) {
            $this->redirect();
            return;
        }

        // WooCommerce validates everything (and emits its own notice on success/failure).
        WC()->cart->apply_coupon($code);

        $this->redirect();
    }

    private function is_rate_limited()
    {
        return (bool) get_transient($this->rate_key());
    }

    private function mark_rate_limited()
    {
        set_transient($this->rate_key(), 1, self::RATE_SECONDS);
    }

    private function rate_key()
    {
        $session = WC()->session ? WC()->session->get_customer_id() : '';
        if (!$session) {
            $session = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'anon';
        }
        return 'hdcl_rl_' . md5((string) $session);
    }

    private function redirect()
    {
        $target = '';
        if (!empty($_GET[self::REDIRECT_VAR])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- validated against the site host on the next line.
            $target = wp_validate_redirect(esc_url_raw(wp_unslash($_GET[self::REDIRECT_VAR])), ''); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see above.
        }
        if ('' === $target) {
            $target = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
        }
        wp_safe_redirect($target);
        exit;
    }
}
