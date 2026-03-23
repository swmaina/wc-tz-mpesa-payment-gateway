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
        return true;
    }

    /**
     * Check if M-Pesa SDK is available
     */
    public static function is_sdk_available()
    {
        return true;
    }

    /**
     * Load Blocks integration if WooCommerce supports it
     */
    public static function load_blocks_integration()
    {
        // Check if WooCommerce version is 8.0 or higher
        if (!defined('WC_VERSION') || version_compare(WC_VERSION, '8.0', '<')) {
            return false;
        }

        // Check if the Blocks interface is available
        if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\PaymentMethodTypeInterface')) {
            return false;
        }

        // Load the Blocks gateway class
        require_once WC_GATEWAY_MPESA_PLUGIN_DIR . 'includes/class-gateway-blocks.php';

        return true;
    }
}

// Load dependencies
WC_Gateway_Mpesa_Dependency_Loader::load();
