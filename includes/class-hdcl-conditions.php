<?php

namespace htrxuan\hdcl;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extra "the cart must..." conditions on a WooCommerce coupon, edited on the coupon screen.
 *
 * "Advanced Coupons for WooCommerce" (<= 4.7.1.1) shipped a stored-XSS bug: a value saved
 * against a coupon was later rendered to storefront visitors without escaping.
 *
 * This class avoids that class of bug by construction:
 *   - Conditions are edited ONLY inside WooCommerce's own coupon-data panel, which is behind
 *     the `edit_shop_coupon` capability and WooCommerce's own coupon nonce. There is no
 *     other write path, front-end or AJAX.
 *   - Every saved value is a number: product ids and category ids are run through absint(),
 *     the quantity/subtotal thresholds through absint()/floatval(). There is NO free-text
 *     condition field, so there is nothing text-shaped to store.
 *   - A condition never produces output. It only ever yields a boolean "met / not met". The
 *     rejection message shown to a shopper is assembled from translated strings and
 *     wc_price() -- it never echoes a stored value.
 */
final class HDCL_Conditions
{

    const META_KEY = '_hdcl_conditions';

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
        add_action('woocommerce_coupon_options', array($this, 'render_fields'), 20);
        add_action('woocommerce_coupon_options_save', array($this, 'save_fields'), 10, 2);

        add_filter('woocommerce_coupon_is_valid', array($this, 'check_valid'), 20, 2);
    }

    /**
     * @param int $coupon_id
     * @return array{products:int[], categories:int[], min_items:int, min_subtotal:float}
     */
    public static function get_conditions($coupon_id)
    {
        $raw = get_post_meta($coupon_id, self::META_KEY, true);
        $raw = is_array($raw) ? $raw : array();
        return array(
            'products'     => isset($raw['products']) && is_array($raw['products']) ? array_values(array_filter(array_map('absint', $raw['products']))) : array(),
            'categories'   => isset($raw['categories']) && is_array($raw['categories']) ? array_values(array_filter(array_map('absint', $raw['categories']))) : array(),
            'min_items'    => isset($raw['min_items']) ? absint($raw['min_items']) : 0,
            'min_subtotal' => isset($raw['min_subtotal']) ? max(0.0, (float) $raw['min_subtotal']) : 0.0,
        );
    }

    public static function has_conditions($coupon_id)
    {
        $c = self::get_conditions($coupon_id);
        return !empty($c['products']) || !empty($c['categories']) || $c['min_items'] > 0 || $c['min_subtotal'] > 0;
    }

    /* ---------- coupon screen ---------- */

    public function render_fields()
    {
        global $post;
        $c = self::get_conditions($post->ID);
        ?>
        <div class="options_group hdcl-conditions">
            <p class="form-field"><strong><?php esc_html_e('HDWebmobile cart conditions', 'hdwebmobile-coupon-links'); ?></strong><br />
                <span class="description"><?php esc_html_e('The cart must satisfy all of these for the coupon to be accepted. Leave any blank to skip it.', 'hdwebmobile-coupon-links'); ?></span>
            </p>

            <p class="form-field">
                <label for="hdcl_products"><?php esc_html_e('Cart must contain product(s)', 'hdwebmobile-coupon-links'); ?></label>
                <select id="hdcl_products" name="hdcl_conditions[products][]" class="wc-product-search" multiple="multiple" style="width:50%;" data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'hdwebmobile-coupon-links'); ?>" data-action="woocommerce_json_search_products_and_variations">
                    <?php foreach ($c['products'] as $product_id) : ?>
                        <?php $product = wc_get_product($product_id); ?>
                        <?php if ($product) : ?>
                            <option value="<?php echo esc_attr($product_id); ?>" selected="selected"><?php echo esc_html(wp_strip_all_tags($product->get_formatted_name())); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="form-field">
                <label for="hdcl_categories"><?php esc_html_e('Cart must contain a product in category', 'hdwebmobile-coupon-links'); ?></label>
                <select id="hdcl_categories" name="hdcl_conditions[categories][]" class="wc-enhanced-select" multiple="multiple" style="width:50%;" data-placeholder="<?php esc_attr_e('Choose categories&hellip;', 'hdwebmobile-coupon-links'); ?>">
                    <?php
                    $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                    if (!is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            printf('<option value="%d"%s>%s</option>', (int) $term->term_id, selected(in_array($term->term_id, $c['categories'], true), true, false), esc_html($term->name));
                        }
                    }
                    ?>
                </select>
            </p>

            <?php
            woocommerce_wp_text_input(array(
                'id'                => 'hdcl_min_items',
                'label'             => __('Minimum item count in cart', 'hdwebmobile-coupon-links'),
                'type'              => 'number',
                'value'             => $c['min_items'] ?: '',
                'custom_attributes' => array('min' => '0', 'step' => '1'),
                'name'              => 'hdcl_conditions[min_items]',
            ));

            woocommerce_wp_text_input(array(
                'id'                => 'hdcl_min_subtotal',
                'label'             => __('Minimum cart subtotal', 'hdwebmobile-coupon-links') . ' (' . get_woocommerce_currency_symbol() . ')',
                'type'              => 'text',
                'data_type'         => 'price',
                'value'             => $c['min_subtotal'] ? wc_format_localized_price($c['min_subtotal']) : '',
                'name'              => 'hdcl_conditions[min_subtotal]',
            ));
            ?>
        </div>
        <?php
    }

    /**
     * @param int        $coupon_id
     * @param \WC_Coupon $coupon
     */
    public function save_fields($coupon_id, $coupon)
    {
        // WooCommerce has already verified its own coupon nonce and the edit_shop_coupon
        // capability before this hook fires. Every value in this array is rebuilt below with
        // absint()/wc_format_decimal() -- nothing from $raw is stored or used as-is.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $raw = isset($_POST['hdcl_conditions']) && is_array($_POST['hdcl_conditions']) ? (array) wp_unslash($_POST['hdcl_conditions']) : array();

        $conditions = array(
            'products'     => isset($raw['products']) && is_array($raw['products']) ? array_values(array_filter(array_map('absint', $raw['products']))) : array(),
            'categories'   => isset($raw['categories']) && is_array($raw['categories']) ? array_values(array_filter(array_map('absint', $raw['categories']))) : array(),
            'min_items'    => isset($raw['min_items']) ? absint($raw['min_items']) : 0,
            'min_subtotal' => isset($raw['min_subtotal']) ? max(0.0, (float) wc_format_decimal($raw['min_subtotal'])) : 0.0,
        );

        if (empty($conditions['products']) && empty($conditions['categories']) && 0 === $conditions['min_items'] && 0.0 === $conditions['min_subtotal']) {
            delete_post_meta($coupon_id, self::META_KEY);
            return;
        }
        update_post_meta($coupon_id, self::META_KEY, $conditions);
    }

    /* ---------- enforcement ---------- */

    /**
     * @param bool       $valid
     * @param \WC_Coupon $coupon
     * @return bool
     * @throws \Exception with a shopper-facing message when a condition is not met.
     */
    public function check_valid($valid, $coupon)
    {
        if (!$valid || !$coupon instanceof \WC_Coupon) {
            return $valid;
        }
        $coupon_id = $coupon->get_id();
        if (!$coupon_id || !self::has_conditions($coupon_id)) {
            return $valid;
        }
        $cart = WC()->cart;
        if (!$cart) {
            return $valid;
        }

        $c = self::get_conditions($coupon_id);

        $cart_product_ids = array();
        $cart_cat_ids     = array();
        $item_count       = 0;
        foreach ($cart->get_cart() as $item) {
            $cart_product_ids[] = (int) $item['product_id'];
            $item_count        += (int) $item['quantity'];
            $terms              = wc_get_product_cat_ids($item['product_id']);
            if (is_array($terms)) {
                $cart_cat_ids = array_merge($cart_cat_ids, array_map('intval', $terms));
            }
        }

        if (!empty($c['products']) && !array_intersect($c['products'], $cart_product_ids)) {
            throw new \Exception(esc_html__('This coupon requires a specific product to be in your cart.', 'hdwebmobile-coupon-links'));
        }
        if (!empty($c['categories']) && !array_intersect($c['categories'], $cart_cat_ids)) {
            throw new \Exception(esc_html__('This coupon requires a product from a specific category to be in your cart.', 'hdwebmobile-coupon-links'));
        }
        if ($c['min_items'] > 0 && $item_count < $c['min_items']) {
            throw new \Exception(esc_html(sprintf(
                /* translators: %d: minimum number of items */
                _n('This coupon requires at least %d item in your cart.', 'This coupon requires at least %d items in your cart.', $c['min_items'], 'hdwebmobile-coupon-links'),
                $c['min_items']
            )));
        }
        if ($c['min_subtotal'] > 0) {
            $subtotal = (float) $cart->get_displayed_subtotal();
            if ($subtotal < $c['min_subtotal']) {
                throw new \Exception(wp_kses_post(sprintf(
                    /* translators: %s: minimum cart subtotal */
                    __('This coupon requires a cart subtotal of at least %s.', 'hdwebmobile-coupon-links'),
                    wc_price($c['min_subtotal'])
                )));
            }
        }

        return $valid;
    }
}
