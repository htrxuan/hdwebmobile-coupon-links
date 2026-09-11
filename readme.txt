=== HDWebmobile Coupon Links & Conditions ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, coupons, coupon url, cart conditions, discount link
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add cart conditions to any coupon, and share links that apply a coupon automatically.

== Description ==

HDWebmobile Coupon Links & Conditions adds two things to WooCommerce's own coupons:

**Cart conditions.** On any coupon's edit screen you can require the cart to contain a specific product, contain a product from a specific category, hold at least N items, or reach a minimum subtotal. If a condition is not met, the coupon is rejected at apply time with a clear message.

**Apply-by-link.** Share a URL like `https://yourstore.com/?hdcl_coupon=SUMMER10` and the coupon is added to the visitor's cart automatically. Optionally append `&hdcl_redirect=/shop/` to send them somewhere afterwards.

= Why this plugin exists =
Coupon plugins are a repeated source of two problems, and this plugin is built so that neither can happen:

* **No stored XSS from a coupon field.** "Advanced Coupons for WooCommerce" (up to and including 4.7.1.1) shipped a stored Cross-Site Scripting vulnerability where a value saved against a coupon was later rendered to storefront visitors without escaping. Here, conditions are edited only inside WooCommerce's own coupon-data panel (behind the `edit_shop_coupon` capability and WooCommerce's coupon nonce), **every stored value is a number** (product IDs, category IDs, a count, a subtotal -- there is no free-text condition field at all), and a condition never produces output: it only yields "met / not met", and the rejection message is built from translated strings and `wc_price()`, never from a stored value.
* **A coupon link can only ever apply an existing, published coupon.** The `hdcl_coupon` value is normalised and looked up with `WC_Coupon`; if it does not resolve to a published coupon, nothing happens. The code is handed straight to WooCommerce's own `apply_coupon()`, so every usage rule (expiry, usage limits, minimum/maximum spend, allowed emails) plus the cart conditions above are still enforced. There is **no code path** that creates a coupon, edits one, or sets a discount amount. The endpoint is also rate-limited per visitor, and any redirect target is passed through `wp_validate_redirect()` (same-site only).

= Key Features =
* Per-coupon cart conditions: required product, required category, minimum item count, minimum subtotal
* Conditions are enforced through WooCommerce's own coupon-validation hook, with clear shopper-facing messages
* Apply-by-link URLs for any published coupon, with an optional same-site redirect
* No settings to save and no database table -- conditions live on the coupon, the link is just a URL
* Works with WooCommerce's classic and block-based Cart and Checkout

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-coupon-links` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. See **WooCommerce > HDWebmobile > Coupon Links & Conditions** for a short how-to and the link format.

== How to Use ==

= 1. Add conditions to a coupon =
Go to **Marketing > Coupons**, edit a coupon, and in the General tab find "HDWebmobile cart conditions". Set any combination of: a required product, a required category, a minimum item count, a minimum subtotal. Save the coupon.

= 2. Share an apply-by-link =
Use `https://yourstore.com/?hdcl_coupon=YOURCODE`. When a visitor opens it, the coupon is applied to their cart (subject to all of WooCommerce's usual rules).

== Screenshots ==

1. The cart-conditions panel on a coupon's edit screen.
2. A rejected coupon showing the condition message on the cart.
3. The Coupon Links & Conditions tab under WooCommerce > HDWebmobile.

== Changelog ==

= 1.0.0 =
* Initial release: per-coupon cart conditions (product / category / item count / subtotal) and rate-limited apply-by-link URLs that only ever apply an existing published coupon.
