/**
 * M-Pesa Payment Gateway - WooCommerce Blocks Checkout Integration
 *
 * @package WC_Gateway_Mpesa\Blocks
 */

const { registerPaymentMethod } = window.wc.blocksRegistry;
const { getSetting } = window.wc.settings;
const { __ } = window.wp.i18n;
const { useEffect, useState } = window.wp.element;
const { useBlockProps } = window.wp.blockEditor || {};

/**
 * Validate phone number (Tanzania E.164 format)
 * Remove non-digits and check if it's 10-12 digits
 *
 * @param {string} phone The phone number to validate
 * @returns {boolean} True if valid, false otherwise
 */
const validatePhoneNumber = (phone) => {
    // Remove non-digits
    const cleaned = phone.replace(/\D/g, '');
    // Tanzania phone numbers should be 10-12 digits
    return cleaned.length >= 10 && cleaned.length <= 12;
};

/**
 * M-Pesa Payment Method Label Component
 */
const MpesaLabel = () => {
    const settings = getSetting('mpesa_data', {});
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            {settings.icon && (
                <img
                    src={settings.icon}
                    alt="M-Pesa"
                    style={{ height: '24px', width: '24px', objectFit: 'contain' }}
                />
            )}
            <span>{settings.title || __('M-Pesa', 'wc-gateway-mpesa')}</span>
        </div>
    );
};

/**
 * M-Pesa Payment Method Content Component
 */
const MpesaContent = () => {
    const settings = getSetting('mpesa_data', {});
    const [phoneNumber, setPhoneNumber] = useState('');
    const [phoneError, setPhoneError] = useState('');

    return (
        <div style={{ marginBottom: '1em' }}>
            {settings.description && (
                <p>{settings.description}</p>
            )}
            <div style={{ marginBottom: '1em' }}>
                <label htmlFor="mpesa-phone-number" style={{ display: 'block', marginBottom: '0.5em', fontWeight: 'bold' }}>
                    {settings.phoneLabel || __('M-Pesa Phone Number', 'wc-gateway-mpesa')}
                    <span style={{ color: 'red' }}>*</span>
                </label>
                <input
                    id="mpesa-phone-number"
                    type="tel"
                    placeholder={settings.phonePlaceholder || __('255700000000', 'wc-gateway-mpesa')}
                    value={phoneNumber}
                    onChange={(e) => {
                        setPhoneNumber(e.target.value);
                        // Clear error when user starts typing
                        if (phoneError) {
                            setPhoneError('');
                        }
                    }}
                    inputMode="tel"
                    style={{
                        width: '100%',
                        padding: '8px',
                        border: phoneError ? '1px solid #d32f2f' : '1px solid #ccc',
                        borderRadius: '4px',
                        fontSize: '1em',
                        boxSizing: 'border-box',
                    }}
                    required
                />
                {phoneError && (
                    <p style={{ color: '#d32f2f', marginTop: '0.5em', marginBottom: 0, fontSize: '0.875em' }}>
                        {phoneError}
                    </p>
                )}
            </div>
        </div>
    );
};

/**
 * Register M-Pesa as a payment method with WooCommerce Blocks
 */
registerPaymentMethod({
    name: 'mpesa',
    label: <MpesaLabel />,
    content: <MpesaContent />,
    edit: <MpesaContent />,
    canMakePayment: () => true,
    ariaLabel: __('M-Pesa payment method', 'wc-gateway-mpesa'),
    supports: {
        features: getSetting('mpesa_data')?.supports || ['products', 'refunds'],
    },
    // Handle payment processing
    placeOrderButtonLabel: __('Pay with M-Pesa', 'wc-gateway-mpesa'),
    shouldSavePaymentMethod: () => false,

    /**
     * Handle the payment processing
     */
    onPaymentProcessing: () => {
        return new Promise((resolve, reject) => {
            // Get payment details from the DOM
            const phoneInput = document.getElementById('mpesa-phone-number');
            const phoneNumber = phoneInput ? phoneInput.value.trim() : '';

            // Validate phone number
            if (!phoneNumber) {
                reject({
                    errorMessage: __('M-Pesa phone number is required.', 'wc-gateway-mpesa'),
                });
                return;
            }

            if (!validatePhoneNumber(phoneNumber)) {
                reject({
                    errorMessage: __('Please enter a valid M-Pesa phone number (10-12 digits).', 'wc-gateway-mpesa'),
                });
                return;
            }

            // Get nonce from settings
            const settings = getSetting('mpesa_data', {});
            const nonce = settings.nonce;

            if (!nonce) {
                reject({
                    errorMessage: __('Security nonce is missing. Please refresh and try again.', 'wc-gateway-mpesa'),
                });
                return;
            }

            // Set payment data in a way that the PHP backend can retrieve
            // WooCommerce Blocks will send this as POST data during checkout
            const { checkout } = window.wc.wcBlocksData;
            
            // Store the phone number in the checkout data
            // This will be available in $_POST['payment_method_data'] on the server
            checkout.setExtensionData('mpesa', {
                phone: phoneNumber,
                nonce: nonce,
            });

            // Resolve to allow checkout to proceed
            resolve();
        });
    },
});
