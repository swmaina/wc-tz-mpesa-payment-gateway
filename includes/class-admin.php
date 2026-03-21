<?php
/**
 * Admin class for M-Pesa Gateway
 *
 * @package WC_Gateway_Mpesa
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Mpesa_Admin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'show_setup_notice'));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts()
    {
        $screen = get_current_screen();

        // Only load on WooCommerce settings page
        if ($screen && strpos($screen->id, 'wc-settings') !== false) {
            wp_enqueue_style(
                'wc-gateway-mpesa-admin',
                WC_GATEWAY_MPESA_PLUGIN_URL . 'assets/admin.css',
                array(),
                WC_GATEWAY_MPESA_VERSION
            );
        }
    }

    /**
     * Show setup notice if gateway not configured
     */
    public function show_setup_notice()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $gateway = new WC_Gateway_Mpesa();

        // Check if gateway is enabled but not configured
        if ('yes' === $gateway->get_option('enabled')) {
            if (empty($gateway->get_option('public_key')) || empty($gateway->get_option('api_key'))) {
                printf(
                    '<div class="notice notice-warning"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
                    esc_html__('WooCommerce M-Pesa Gateway:', 'wc-gateway-mpesa'),
                    esc_html__('Please configure your M-Pesa credentials.', 'wc-gateway-mpesa'),
                    esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=mpesa')),
                    esc_html__('Configure now', 'wc-gateway-mpesa')
                );
            }
        }
    }
}
