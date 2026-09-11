# HDWebmobile Coupon Links & Conditions

Add cart conditions to any WooCommerce coupon, and share links that apply a coupon automatically. A coupon link only ever applies an existing published coupon by exact code — it can't create one or change a discount.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-coupon-links/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

**Cart conditions** — on a coupon's edit screen, require the cart to contain a specific product, contain a category, hold at least N items, or reach a minimum subtotal. Unmet conditions reject the coupon with a clear message.

**Apply-by-link** — `https://yourstore.com/?hdcl_coupon=SUMMER10` adds the coupon to the visitor's cart. Optional `&hdcl_redirect=/shop/` (same-site only).

## Why this plugin exists

Coupon plugins keep shipping two problems; this one is built so neither can happen:

* **No stored XSS from a coupon field.** "Advanced Coupons for WooCommerce" (≤ 4.7.1.1) shipped stored XSS via a value saved against a coupon. Here conditions are edited only in WooCommerce's own coupon panel (`edit_shop_coupon` + WC coupon nonce), **every stored value is a number** (no free-text condition field), and a condition never produces output — only "met / not met", with rejection messages built from translated strings + `wc_price()`.
* **A coupon link can only apply an existing published coupon.** Looked up via `WC_Coupon`; if it doesn't resolve to a published coupon, nothing happens. Handed to WooCommerce's own `apply_coupon()`, so every usage rule still applies. No code path creates or edits a coupon or sets a discount. Rate-limited per visitor; redirects pass through `wp_validate_redirect()`.

## Features

* Per-coupon conditions: required product, required category, minimum item count, minimum subtotal
* Enforced through WooCommerce's own coupon-validation hook
* Apply-by-link URLs for any published coupon, optional same-site redirect
* No settings, no database table — conditions live on the coupon
* Classic and block Cart / Checkout

## Installation

1. Upload to `/wp-content/plugins/hdwebmobile-coupon-links`, or install through the WordPress plugins screen.
2. Activate. WooCommerce must already be installed and active.
3. See **WooCommerce > HDWebmobile > Coupon Links & Conditions**.

## License

GPLv2 or later — https://www.gnu.org/licenses/gpl-2.0.html
