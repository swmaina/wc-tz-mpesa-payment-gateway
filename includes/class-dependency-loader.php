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

        // Load dependencies manually
        self::load_dependencies_manually();
        return true;
    }

    /**
     * Load dependencies manually without Composer autoloader
     */
    private static function load_dependencies_manually()
    {
        $base_path = WC_GATEWAY_MPESA_PLUGIN_DIR . 'vendor/';

        // Load Guzzle classes (simplified - only load what's needed)
        require_once $base_path . 'guzzlehttp/guzzle/src/functions.php';
        require_once $base_path . 'guzzlehttp/guzzle/src/Client.php';
        // Add more Guzzle files as needed

        // Load M-Pesa SDK classes
        $sdk_files = array(
            'karson/mpesa-php-sdk/src/Exceptions/MpesaException.php',
            'karson/mpesa-php-sdk/src/Exceptions/ValidationException.php',
            'karson/mpesa-php-sdk/src/Exceptions/AuthenticationException.php',
            'karson/mpesa-php-sdk/src/Exceptions/ApiException.php',
            'karson/mpesa-php-sdk/src/Constants/ResponseCodes.php',
            'karson/mpesa-php-sdk/src/Constants/TransactionStatus.php',
            'karson/mpesa-php-sdk/src/Validation/ParameterValidator.php',
            'karson/mpesa-php-sdk/src/Response/BaseResponse.php',
            'karson/mpesa-php-sdk/src/Response/TransactionResponse.php',
            'karson/mpesa-php-sdk/src/Response/TransactionStatusResponse.php',
            'karson/mpesa-php-sdk/src/Response/CustomerNameResponse.php',
            'karson/mpesa-php-sdk/src/Response/ReversalResponse.php',
            'karson/mpesa-php-sdk/src/Auth/TokenManager.php',
            'karson/mpesa-php-sdk/src/Mpesa.php',
        );

        foreach ($sdk_files as $file) {
            $full_path = $base_path . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            }
        }
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
                    '<a href="' . esc_url(wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . WC_GATEWAY_MPESA_BASENAME), 'activate-plugin_' . WC_GATEWAY_MPESA_BASENAME)) . '">reinstall</a>'
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
