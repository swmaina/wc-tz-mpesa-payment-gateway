<?php
/**
 * Plugin Name: WooCommerce M-Pesa Payment Gateway
 * Plugin URI: https://github.com/yourusername/wc-gateway-mpesa
 * Description: Accept M-Pesa payments in your WooCommerce store. Works with Tanzanian and East African M-Pesa providers.
 * Version: 1.0.0
 * Author: MindSafe Solutions
 * Author URI: https://mindsafe.co.ke
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-gateway-mpesa
 * Domain Path: /languages
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_GATEWAY_MPESA_VERSION', '1.0.0');
define('WC_GATEWAY_MPESA_PLUGIN_FILE', __FILE__);
define('WC_GATEWAY_MPESA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_GATEWAY_MPESA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_GATEWAY_MPESA_BASENAME', plugin_basename(__FILE__));

// ─── Declare HPOS Compatibility ───────────────────────────────────────────────
// Add this near the top of your plugin
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true // true = compatible, false = not compatible
		);
	}
} );

/**
 * Main plugin class
 */
class WC_Gateway_Mpesa_Plugin
{
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Check dependencies
        add_action('plugins_loaded', array($this, 'check_dependencies'));
        
        // Load translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'), 10);
    }

    /**
     * Check if WooCommerce is installed and activated
     */
    public function check_dependencies()
    {
        // Check WooCommerce
        if (!class_exists('WC_Payment_Gateway')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice()
    {
        printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
            esc_html__('WooCommerce M-Pesa Gateway Error:', 'wc-gateway-mpesa'),
            esc_html__('WooCommerce must be installed and activated for this plugin to work.', 'wc-gateway-mpesa')
        );
    }

    /**
     * PHP version notice
     */
    public function php_version_notice()
    {
        printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
            esc_html__('WooCommerce M-Pesa Gateway Error:', 'wc-gateway-mpesa'),
            sprintf(
                esc_html__('This plugin requires PHP 7.4 or higher. Your current PHP version is %s.', 'wc-gateway-mpesa'),
                PHP_VERSION
            )
        );
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'wc-gateway-mpesa',
            false,
            dirname(WC_GATEWAY_MPESA_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize the plugin
     */
    public function init()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        // Load dependencies
        $this->load_dependencies();

        // Declare Blocks compatibility
        $this->declare_blocks_compatibility();

        // Register payment gateway
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));

        // Register Blocks payment method
        add_action('woocommerce_blocks_payment_method_type_registration', array($this, 'register_blocks_payment_method'));

        // Add gateway settings link
        add_filter('plugin_action_links_' . WC_GATEWAY_MPESA_BASENAME, array($this, 'add_settings_link'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies()
    {
        // Require files in order
        require_once WC_GATEWAY_MPESA_PLUGIN_DIR . 'includes/class-dependency-loader.php';
        require_once WC_GATEWAY_MPESA_PLUGIN_DIR . 'includes/class-admin.php';
        require_once WC_GATEWAY_MPESA_PLUGIN_DIR . 'includes/class-gateway.php';

        // Load Blocks integration if available
        WC_Gateway_Mpesa_Dependency_Loader::load_blocks_integration();

        // Initialize admin class
        new WC_Gateway_Mpesa_Admin();
    }

    /**
     * Register the payment gateway
     */
    public function add_gateway($gateways)
    {
        $gateways[] = 'WC_Gateway_Mpesa';
        return $gateways;
    }

    /**
     * Declare Blocks compatibility for WooCommerce Blocks checkout
     */
    private function declare_blocks_compatibility()
    {
        // Only declare compatibility if WooCommerce Blocks is available
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                WC_GATEWAY_MPESA_PLUGIN_FILE,
                true
            );
        }
    }

    /**
     * Register Blocks payment method type
     *
     * @param \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry Payment method registry.
     */
    public function register_blocks_payment_method($payment_method_registry)
    {
        // Only proceed if Blocks integration is available
        if (!class_exists('WC_Gateway_Mpesa_Blocks')) {
            return;
        }

        // Get the gateway instance
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        if (!isset($gateways['mpesa'])) {
            return;
        }

        $gateway = $gateways['mpesa'];

        // Register the Blocks payment method
        $payment_method_registry->register(
            new WC_Gateway_Mpesa_Blocks($gateway)
        );
    }

    /**
     * Add settings link on plugins page
     */
    public function add_settings_link($links)
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=wc-settings&tab=checkout&section=mpesa'),
            esc_html__('Settings', 'wc-gateway-mpesa')
        );
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize plugin
WC_Gateway_Mpesa_Plugin::get_instance();

// Activation/Deactivation hooks
register_activation_hook(__FILE__, 'wc_gateway_mpesa_activate');
register_deactivation_hook(__FILE__, 'wc_gateway_mpesa_deactivate');

function wc_gateway_mpesa_activate()
{
    // Set transient to show activation notice
    set_transient('wc_gateway_mpesa_activated', true, 5 * 60);
}

function wc_gateway_mpesa_deactivate()
{
    // Cleanup on deactivation
    delete_transient('wc_gateway_mpesa_activated');
}
