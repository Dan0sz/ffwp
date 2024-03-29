/**
 * EDD Better Checkout for FFW.Press
 * 
 * @author Daan van den Bergh
 * @url    https://ffw.press
 */
var edd_global_vars;

jQuery(document).ready(function ($) {
    var ffwp_checkout = {
        init: function () {
            $(document.body).on('edd_taxes_recalculated', this.add_class);

            /**
             * Since the discount form is moved outside the form, we're triggering a click on 
             * a hidden input field inside the form.
             */
            $('#ffwpress_checkout_shopping_cart .edd-apply-discount').on('click', function () {
                $('#edd_checkout_form_wrap .edd-apply-discount').click()
            });

            /**
             * Events after which the shopping cart is refreshed.
             */
            $(document).on('edd_cart_billing_address_updated', this.set_loader_cart);
            $(document).on('edd_eu_vat:before_vat_check', this.set_loader_cart);
            $('#billing_country').on('change', this.set_loader_cart);
            $('.edd-apply-discount').on('click', this.set_loader_cart);
        },

        add_class: function () {
            var $result_data = $('#edd-vat-check-result').data('valid');
            var $validate_button = $('#edd-vat-check-button');

            if ($result_data === 1) {
                $validate_button.addClass('ffwp-vat-valid');
                $validate_button.val('Valid');
            } else {
                $validate_button.removeClass('ffwp-vat-valid');
                $validate_button.val('Validate');
            }
        },

        set_loader_cart: function () {
            var $cart = $('#edd_checkout_cart');

            $cart.append('<span class="ffwp-loader edd-loading-ajax edd-loading"></span>');
            $cart.css({
                opacity: 0.5
            });
        }
    };

    ffwp_checkout.init();
});