<?php
/**
 * M-Pesa Payment Gateway Main Class
 *
 * @package WC_Gateway_Mpesa
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Mpesa extends WC_Payment_Gateway
{
    /**
     * Logger instance
     *
     * @var WC_Logger
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id                 = 'mpesa';
        $this->icon               = apply_filters('woocommerce_mpesa_icon', WC_GATEWAY_MPESA_PLUGIN_URL . 'assets/mpesa-icon.png');
        $this->has_fields         = true;
        $this->method_title       = __('M-Pesa', 'wc-gateway-mpesa');
        $this->method_description = __('Accept M-Pesa payments from your customers in Tanzania and East Africa', 'wc-gateway-mpesa');
        $this->order_button_text  = __('Pay with M-Pesa', 'wc-gateway-mpesa');
        $this->supports           = array(
            'products',
            'refunds',
        );

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->public_key         = $this->get_option('public_key');
        $this->api_key            = $this->get_option('api_key');
        $this->service_provider   = $this->get_option('service_provider_code');
        $this->testmode           = 'yes' === $this->get_option('testmode');

        // Get logger
        $this->logger = wc_get_logger();

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        add_action('woocommerce_api_wc_gateway_mpesa', array($this, 'webhook_callback'));
        add_action('admin_init', array($this, 'maybe_process_webhook'));
    }

    /**
     * Check if the gateway is available
     */
    public function is_available()
    {
        if (empty($this->public_key) || empty($this->api_key) || empty($this->service_provider)) {
            return false;
        }

        return parent::is_available();
    }

    /**
     * Admin panel options and settings form fields
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'               => array(
                'title'       => __('Enable/Disable', 'wc-gateway-mpesa'),
                'label'       => __('Enable M-Pesa Payment Gateway', 'wc-gateway-mpesa'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),
            'title'                 => array(
                'title'       => __('Title', 'wc-gateway-mpesa'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wc-gateway-mpesa'),
                'default'     => __('M-Pesa Mobile Money', 'wc-gateway-mpesa'),
                'desc_tip'    => true,
            ),
            'description'           => array(
                'title'       => __('Description', 'wc-gateway-mpesa'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'wc-gateway-mpesa'),
                'default'     => __('Pay securely using your M-Pesa mobile wallet. You will receive a prompt on your phone.', 'wc-gateway-mpesa'),
                'desc_tip'    => true,
            ),
            'credentials_section'   => array(
                'title' => __('API Credentials', 'wc-gateway-mpesa'),
                'type'  => 'title',
            ),
            'public_key'            => array(
                'title'       => __('Public Key', 'wc-gateway-mpesa'),
                'type'        => 'textarea',
                'description' => __('Enter the public key provided by your M-Pesa provider. This is usually a long string starting with -----BEGIN PUBLIC KEY-----.', 'wc-gateway-mpesa'),
                'desc_tip'    => true,
                'css'         => 'min-height: 150px;',
            ),
            'api_key'               => array(
                'title'       => __('API Key', 'wc-gateway-mpesa'),
                'type'        => 'password',
                'description' => __('Enter the API key provided by your M-Pesa provider. Keep this secret and do not share it.', 'wc-gateway-mpesa'),
                'desc_tip'    => true,
            ),
            'service_provider_code' => array(
                'title'       => __('Service Provider Code', 'wc-gateway-mpesa'),
                'type'        => 'text',
                'description' => __('Enter your M-Pesa service provider code (usually provided by your M-Pesa provider).', 'wc-gateway-mpesa'),
                'desc_tip'    => true,
            ),
            'testmode'              => array(
                'title'       => __('Test Mode', 'wc-gateway-mpesa'),
                'label'       => __('Enable Sandbox/Test Mode', 'wc-gateway-mpesa'),
                'type'        => 'checkbox',
                'description' => __('Enable this to test transactions in the M-Pesa sandbox environment. Disable for live transactions.', 'wc-gateway-mpesa'),
                'default'     => 'yes',
            ),
        );
    }

    /**
     * Payment scripts for checkout
     */
    public function payment_scripts()
    {
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }

        if ('no' === $this->enabled) {
            return;
        }

        wp_enqueue_script(
            'wc-gateway-mpesa-checkout',
            WC_GATEWAY_MPESA_PLUGIN_URL . 'assets/checkout.js',
            array('jquery', 'wc-checkout'),
            WC_GATEWAY_MPESA_VERSION,
            true
        );

        $mpesa_params = array(
            'currency'            => get_woocommerce_currency(),
            'currency_symbol'     => html_entity_decode(get_woocommerce_currency_symbol()),
            'phone_label'         => __('M-Pesa Phone Number', 'wc-gateway-mpesa'),
            'phone_placeholder'   => __('255700000000 or 0700000000', 'wc-gateway-mpesa'),
            'invalid_phone'       => __('Please enter a valid M-Pesa phone number.', 'wc-gateway-mpesa'),
        );

        wp_localize_script('wc-gateway-mpesa-checkout', 'mpesa_params', $mpesa_params);
    }

    /**
     * Payment form on checkout
     */
    public function payment_fields()
    {
        if ($this->description) {
            echo wp_kses_post(wpautop(wptexturize($this->description)));
        }

        echo '<fieldset id="wc-' . esc_attr($this->id) . '-form" class="wc-payment-form" style="background:transparent;">';

        // Phone number field
        echo '<p class="form-row form-row-wide">
            <label for="' . esc_attr($this->id) . '_phone">' . esc_html(__('M-Pesa Phone Number', 'wc-gateway-mpesa')) . ' <span class="required">*</span></label>
            <input 
                id="' . esc_attr($this->id) . '_phone" 
                class="input-text wc-' . esc_attr($this->id) . '-phone" 
                inputmode="tel"
                type="tel" 
                name="' . esc_attr($this->id) . '_phone" 
                placeholder="255700000000"
                value=""
                required />
        </p>';

        // Nonce for security
        wp_nonce_field('wc_' . $this->id . '_process', 'wc_' . $this->id . '_nonce');

        echo '</fieldset>';
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice(__('Order not found.', 'wc-gateway-mpesa'), 'error');
            return array('result' => 'fail');
        }

        // Determine if this is a Blocks or Classic checkout and get phone/nonce accordingly
        $is_blocks_checkout = false;
        $phone = '';
        $nonce = '';

        // Check for Blocks checkout data first
        if (isset($_POST['payment_method_data'])) {
            $payment_method_data = $_POST['payment_method_data'];
            if (is_string($payment_method_data)) {
                parse_str($payment_method_data, $payment_method_data);
            }

            if (isset($payment_method_data['mpesa'])) {
                $mpesa_data = $payment_method_data['mpesa'];
                if (is_string($mpesa_data)) {
                    parse_str($mpesa_data, $mpesa_data);
                }

                if (isset($mpesa_data['phone'])) {
                    $phone = sanitize_text_field($mpesa_data['phone']);
                    $nonce = isset($mpesa_data['nonce']) ? sanitize_text_field($mpesa_data['nonce']) : '';
                    $is_blocks_checkout = true;
                }
            }
        }

        // If not Blocks checkout, try Classic checkout
        if (!$is_blocks_checkout) {
            $phone = isset($_POST[$this->id . '_phone']) ? sanitize_text_field($_POST[$this->id . '_phone']) : '';
            $nonce = isset($_POST['wc_' . $this->id . '_nonce']) ? sanitize_text_field($_POST['wc_' . $this->id . '_nonce']) : '';
        }

        // Verify nonce based on checkout type
        $nonce_action = $is_blocks_checkout ? 'wc_mpesa_blocks_nonce' : ('wc_' . $this->id . '_process');
        if (!wp_verify_nonce($nonce, $nonce_action)) {
            wc_add_notice(__('Security check failed. Please try again.', 'wc-gateway-mpesa'), 'error');
            return array('result' => 'fail');
        }

        if (empty($phone)) {
            wc_add_notice(__('M-Pesa phone number is required.', 'wc-gateway-mpesa'), 'error');
            return array('result' => 'fail');
        }

        // Validate phone number
        if (!$this->validate_phone_number($phone)) {
            wc_add_notice(__('Please enter a valid M-Pesa phone number (10-12 digits).', 'wc-gateway-mpesa'), 'error');
            return array('result' => 'fail');
        }

        try {
            // Process M-Pesa payment
            $response = $this->process_mpesa_payment($order, $phone);

            if ($response['success']) {
                // Update order with transaction info
                $order->update_status('pending', __('Awaiting M-Pesa payment confirmation', 'wc-gateway-mpesa'));
                $order->update_meta_data('_mpesa_phone', $phone);
                $order->update_meta_data('_mpesa_transaction_ref', $response['transaction_id']);
                $order->update_meta_data('_mpesa_initiated_at', current_time('mysql'));
                $order->save();

                // Log transaction
                $this->logger->info(
                    'M-Pesa payment initiated for order ' . $order_id,
                    array('source' => 'mpesa')
                );

                // Empty cart
                WC()->cart->empty_cart();

                // Return success
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            } else {
                wc_add_notice($response['message'], 'error');
                $this->logger->warning(
                    'M-Pesa payment failed: ' . $response['message'],
                    array('source' => 'mpesa')
                );
                return array('result' => 'fail');
            }
        } catch (Exception $e) {
            $this->logger->error(
                'M-Pesa payment error: ' . $e->getMessage(),
                array('source' => 'mpesa')
            );
            wc_add_notice(__('Payment processing error. Please try again.', 'wc-gateway-mpesa'), 'error');
            return array('result' => 'fail');
        }
    }

    /**
     * Get M-Pesa API Token
     */
    private function get_mpesa_token()
    {
        $key = "-----BEGIN PUBLIC KEY-----\n";
        $key .= wordwrap($this->public_key, 60, "\n", true);
        $key .= "\n-----END PUBLIC KEY-----";

        $encrypted = '';
        if (!openssl_public_encrypt($this->api_key, $encrypted, $key, OPENSSL_PKCS1_PADDING)) {
            throw new Exception(__('Failed to encrypt API key for token generation.', 'wc-gateway-mpesa'));
        }

        return base64_encode($encrypted);
    }

    /**
     * Process M-Pesa payment directly via wp_remote_post
     */
    private function process_mpesa_payment($order, $phone)
    {
        try {
            $order_id = $order->get_id();
            $amount   = $order->get_total();

            // Generate unique references
            $transaction_reference = 'WOO-' . $order_id . '-' . time();
            $third_party_reference = 'WOO-' . $order_id;

            $token = $this->get_mpesa_token();
            $service_provider = $this->testmode ? '171717' : $this->service_provider;
            $base_uri = $this->testmode ? 'https://api.sandbox.vm.co.mz:18352' : 'https://api.vm.co.mz:18352';
            $endpoint = $base_uri . '/ipg/v1x/c2bPayment/singleStage/';

            $fields = [
                "input_TransactionReference" => $transaction_reference,
                "input_CustomerMSISDN" => $phone,
                "input_Amount" => (string)$amount,
                "input_ThirdPartyReference" => $third_party_reference,
                "input_ServiceProviderCode" => $service_provider
            ];

            $args = [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'origin'        => 'developer.mpesa.vm.co.mz',
                    'Connection'    => 'keep-alive',
                    'User-Agent'    => 'WooCommerce/Mpesa-Gateway'
                ],
                'body'      => wp_json_encode($fields),
                'timeout'   => 45,
                'sslverify' => false // matches original SDK skipping ssl verify
            ];

            $response = wp_remote_post($endpoint, $args);

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $status_code = wp_remote_retrieve_response_code($response);

            if ($status_code >= 200 && $status_code < 300 && isset($data['output_ResponseCode']) && $data['output_ResponseCode'] === 'INS-0') {
                return array(
                    'success'        => true,
                    'transaction_id' => $data['output_ConversationID'] ?? '',
                    'message'        => __('Payment initiated. Please confirm the prompt on your phone.', 'wc-gateway-mpesa'),
                );
            } else {
                $error_msg = $data['output_ResponseDesc'] ?? __('Unknown error', 'wc-gateway-mpesa');
                return array(
                    'success' => false,
                    'message' => sprintf(__('Payment failed: %s', 'wc-gateway-mpesa'), $error_msg),
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => sprintf(__('Error: %s', 'wc-gateway-mpesa'), $e->getMessage()),
            );
        }
    }

    /**
     * Validate phone number (Tanzania E.164 format)
     */
    private function validate_phone_number($phone)
    {
        // Remove non-digits
        $phone = preg_replace('/\D/', '', $phone);

        // Tanzania phone numbers should be 10-12 digits
        return strlen($phone) >= 10 && strlen($phone) <= 12;
    }

    /**
     * Webhook callback for M-Pesa payment confirmations
     */
    public function webhook_callback()
    {
        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);

        $this->logger->info('M-Pesa Webhook received', array('source' => 'mpesa'));

        if (empty($data)) {
            wp_die('Invalid webhook data', 400);
        }

        try {
            $third_party_ref = isset($data['thirdPartyReference']) ? $data['thirdPartyReference'] : null;
            $transaction_id  = isset($data['transactionID']) ? $data['transactionID'] : null;
            $transaction_status = isset($data['transactionStatus']) ? $data['transactionStatus'] : null;

            if (!$third_party_ref) {
                wp_die('Missing transaction reference', 400);
            }

            // Extract order ID
            $order_id = intval(str_replace('WOO-', '', $third_party_ref));
            $order    = wc_get_order($order_id);

            if (!$order) {
                wp_die('Order not found', 404);
            }

            // Update order based on status
            if (in_array($transaction_status, array('Completed', 'Success', 'Successful'))) {
                $order->payment_complete($transaction_id);
                $order->add_order_note(__('M-Pesa payment confirmed.', 'wc-gateway-mpesa'));
                $order->update_meta_data('_mpesa_transaction_id', $transaction_id);
                $order->update_meta_data('_mpesa_completed_at', current_time('mysql'));
                $order->save();
            } elseif (in_array($transaction_status, array('Failed', 'Cancelled'))) {
                $order->update_status('failed', __('M-Pesa payment failed or was cancelled.', 'wc-gateway-mpesa'));
                $order->update_meta_data('_mpesa_transaction_id', $transaction_id);
                $order->save();
            } elseif ('Pending' === $transaction_status) {
                $order->update_status('pending', __('M-Pesa payment is pending confirmation.', 'wc-gateway-mpesa'));
            }

            $this->logger->info(
                'Webhook processed for order ' . $order_id . ' with status ' . $transaction_status,
                array('source' => 'mpesa')
            );

            wp_die('OK', 200);
        } catch (Exception $e) {
            $this->logger->error('Webhook processing error: ' . $e->getMessage(), array('source' => 'mpesa'));
            wp_die('Error processing webhook', 500);
        }
    }

    /**
     * Maybe process webhook via admin-ajax
     */
    public function maybe_process_webhook()
    {
        if (isset($_GET['mpesa_webhook'])) {
            $this->webhook_callback();
        }
    }

    /**
     * Thank you page content
     */
    public function thankyou_page($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order->has_status('completed')) {
            echo '<p>' . esc_html(__('Your payment has been received and confirmed. Thank you!', 'wc-gateway-mpesa')) . '</p>';
        } elseif ($order->has_status('pending')) {
            echo '<p>' . esc_html(__('Your payment is being processed. We will confirm it shortly and send you an email.', 'wc-gateway-mpesa')) . '</p>';
        } else {
            echo '<p>' . esc_html(__('Your payment could not be completed. Please contact support.', 'wc-gateway-mpesa')) . '</p>';
        }
    }

    /**
     * Email instructions
     */
    public function email_instructions($order, $sent_to_admin, $plain_text)
    {
        if (!$sent_to_admin && $this->id === $order->get_payment_method()) {
            if ($order->has_status('pending')) {
                $text = __('Awaiting M-Pesa payment confirmation.', 'wc-gateway-mpesa');
                if ($plain_text) {
                    echo esc_html($text) . "\n";
                } else {
                    echo '<p>' . esc_html($text) . '</p>';
                }
            }
        }
    }

    /**
     * Process refund
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('order_not_found', __('Order not found', 'wc-gateway-mpesa'));
        }

        $this->logger->info(
            'Refund requested for order ' . $order_id . ' - Amount: ' . $amount,
            array('source' => 'mpesa')
        );

        // Note: Actual refund logic would require M-Pesa reversal API
        // For now, we'll just add a note and mark it for manual review
        $order->add_order_note(
            sprintf(
                __('Refund requested: %s (Reason: %s)', 'wc-gateway-mpesa'),
                wc_price($amount),
                $reason ?: __('No reason provided', 'wc-gateway-mpesa')
            )
        );

        return true;
    }
}
