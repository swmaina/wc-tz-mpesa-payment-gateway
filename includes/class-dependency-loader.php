<?php
/**
 * Dependency Loader for M-Pesa SDK
 *
 * Handles loading the M-Pesa SDK without requiring composer
 *
 * @package WC_Gateway_Mpesa
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Mpesa_Dependency_Loader
{
    /**
     * Load M-Pesa SDK
     */
    public static function load()
    {
        $vendor_dir = WC_GATEWAY_MPESA_PLUGIN_DIR . 'vendor';

        // Check if vendor directory exists
        if (!file_exists($vendor_dir)) {
            self::show_vendor_error();
            return false;
        }

        // Load autoloader
        if (file_exists($vendor_dir . '/autoload.php')) {
            require_once $vendor_dir . '/autoload.php';
            return true;
        }

        self::show_vendor_error();
        return false;
    }

    /**
     * Show error if vendor files are missing
     */
    private static function show_vendor_error()
    {
        add_action('admin_notices', function() {
            printf(
                '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
                esc_html__('WooCommerce M-Pesa Gateway:', 'wc-gateway-mpesa'),
                sprintf(
                    esc_html__('Required dependencies are missing. Please %s to activate the plugin.', 'wc-gateway-mpesa'),
                    '<a href="' + esc_url(wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' + WC_GATEWAY_MPESA_BASENAME), 'activate-plugin_' + WC_GATEWAY_MPESA_BASENAME)) + '">reinstall</a>'
                )
            );
        });
    }

    /**
     * Check if M-Pesa SDK is available
     */
    public static function is_sdk_available()
    {
        return class_exists('Karson\MpesaPhpSdk\Mpesa');
    }
}

// Load dependencies
WC_Gateway_Mpesa_Dependency_Loader::load();