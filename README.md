=== WooCommerce M-Pesa Payment Gateway ===
Contributors: yourname
Tags: woocommerce, payment, gateway, m-pesa, tanzania, east africa, mobile money
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.4
WC requires at least: 4.0
WC tested up to: 8.5
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept M-Pesa payments directly in your WooCommerce store. Perfect for Tanzania and East Africa.

== Description ==

WooCommerce M-Pesa Payment Gateway allows you to accept payments from your customers using M-Pesa mobile money service. This plugin integrates seamlessly with WooCommerce and provides a secure, reliable payment solution for the Tanzanian and East African market.

**Features:**

- Accept M-Pesa payments directly from checkout
- Real-time payment verification
- Automatic order status updates
- Webhook support for payment confirmations
- Test/Sandbox mode for safe testing
- Responsive mobile-friendly interface
- Comprehensive error handling and logging
- Full WooCommerce integration
- Support for both production and test environments

**Requirements:**

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+
- Active M-Pesa merchant account

== Installation ==

1. Download the plugin from WordPress.org
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded file
4. Click "Install Now" and then "Activate"
5. Go to WooCommerce > Settings > Payments
6. Click on "M-Pesa" and configure your API credentials
7. Save your settings

== Configuration ==

1. Get your M-Pesa API credentials (Public Key, API Key, and Service Provider Code)
2. Go to WooCommerce Settings > Payments > M-Pesa
3. Enable the payment method
4. Enter your credentials
5. Enable Test Mode for testing (disable for production)
6. Save changes

== Frequently Asked Questions ==

= How do I get my M-Pesa API credentials? =
Contact your M-Pesa provider (Vodacom, Airtel, Tigo, etc.) for your API credentials.

= Can I test the payment gateway before going live? =
Yes! Enable "Test Mode" in the gateway settings to use the sandbox environment.

= What should I do if I receive an error? =
Check the WooCommerce logs (WooCommerce > Status > Logs > mpesa) for detailed error information.

= Is my data secure? =
Yes. We use encrypted connections and follow WordPress/WooCommerce security best practices.

== Support ==

For support, please visit: https://github.com/yourusername/wc-gateway-mpesa

== Changelog ==

= 1.0.0 =
- Initial release
- M-Pesa C2B payment support
- Webhook confirmation handling
- Test mode support
- Full WooCommerce integration

== License ==

This plugin is licensed under the GPL v2 or later. See LICENSE.md for details.
