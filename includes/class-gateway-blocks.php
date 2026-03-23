<?php
/**
 * M-Pesa Payment Gateway Blocks Integration
 *
 * @package WC_Gateway_Mpesa\Blocks
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Blocks payment method integration for M-Pesa
 *
 * @package WC_Gateway_Mpesa
 */
class WC_Gateway_Mpesa_Blocks extends AbstractPaymentMethodType
{
    /**
     * Reference to the payment gateway.
     *
     * @var WC_Gateway_Mpesa
     */
    private $gateway;

    /**
     * Constructor
     *
     * @param WC_Gateway_Mpesa $gateway The M-Pesa gateway instance.
     */
    public function __construct(WC_Gateway_Mpesa $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->name = 'mpesa';
        $this->settings = get_option('woocommerce_mpesa_settings', array());
    }

    /**
     * Returns whether the payment method is active.
     */
    public function is_active()
    {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of script data to localize on the page.
     *
     * @return array Array of payment method script data.
     */
    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'wc-gateway-mpesa-blocks',
            WC_GATEWAY_MPESA_PLUGIN_URL . 'assets/checkout-blocks.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-components',
                'wp-i18n',
            ),
            WC_GATEWAY_MPESA_VERSION,
            true
        );

        // Register and enqueue optional styles
        wp_register_style(
            'wc-gateway-mpesa-blocks-style',
            WC_GATEWAY_MPESA_PLUGIN_URL . 'assets/checkout-blocks.css',
            array(),
            WC_GATEWAY_MPESA_VERSION
        );
        wp_enqueue_style('wc-gateway-mpesa-blocks-style');

        return array('wc-gateway-mpesa-blocks');
    }

    /**
     * Returns an array of script dependencies.
     *
     * @return array Array of script dependencies.
     */
    public function get_payment_method_script_dependencies()
    {
        return array('wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-components', 'wp-i18n');
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment method script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return array(
            'title'       => $this->gateway->get_option('title'),
            'description' => $this->gateway->get_option('description'),
            'supports'    => array(
                'products',
                'refunds',
            ),
            'icon'        => WC_GATEWAY_MPESA_PLUGIN_URL . 'assets/mpesa-icon.png',
            'phoneLabel'  => __('M-Pesa Phone Number', 'wc-gateway-mpesa'),
            'phonePlaceholder' => __('255700000000 or 0700000000', 'wc-gateway-mpesa'),
            'invalidPhone' => __('Please enter a valid M-Pesa phone number.', 'wc-gateway-mpesa'),
            'nonce'       => wp_create_nonce('wc_mpesa_blocks_nonce'),
        );
    }
}
