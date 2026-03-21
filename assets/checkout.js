(function($) {
    'use strict';

    var WC_Mpesa = {
        init: function() {
            $(document.body).on('checkout_place_order_mpesa', this.validate_fields);
        },

        validate_fields: function() {
            var phone = $('#mpesa_phone').val();

            if (!phone) {
                jQuery('.woocommerce-error').remove();
                jQuery('form.checkout').prepend(
                    '<div class="woocommerce-error">' + 
                    mpesa_params.invalid_phone + 
                    '</div>'
                );
                jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
                return false;
            }

            // Remove non-digits
            var cleaned = phone.replace(/\D/g, '');

            // Validate length
            if (cleaned.length < 10 || cleaned.length > 12) {
                jQuery('.woocommerce-error').remove();
                jQuery('form.checkout').prepend(
                    '<div class="woocommerce-error">' + 
                    mpesa_params.invalid_phone + 
                    '</div>'
                );
                jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
                return false;
            }

            return true;
        }
    };

    WC_Mpesa.init();

})(jQuery);
