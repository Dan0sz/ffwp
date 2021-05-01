<?php

/**
 * @package   FFWP Better Checkout
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_BetterCheckout_Enable
{
    /**
     * List of translateable texts that should be rewritten.
     * 
     * Format: Rewritten text => Text to be translated.
     */
    const FFWP_BETTER_CHECKOUT_REWRITE_TEXT_FIELDS = [
        'Your Details'                 => 'Personal Info',
        'Street + House No.'           => 'Billing Address',
        'Suite, Apt No., PO Box, etc.' => 'Billing Address Line 2 (optional)',
        'Zip/Postal Code'              => 'Billing Zip / Postal Code',
        'City'                         => 'Billing City',
        'Country'                      => 'Billing Country',
        'State/Province'               => 'Billing State / Province',
        'Validate'                     => 'Check',
        'Payment Method <span class="ffwpress-secure-lock"><i class="icon-lock"></i>Secure transaction</span>' => 'Select Payment Method'
    ];

    private $plugin_dir = '';

    /**
     * FFWP_BetterCheckout_Enable constructor.
     */
    public function __construct()
    {
        $this->plugin_dir = plugin_dir_path(__FILE__);

        add_action('wp_head', [$this, 'replace_shortcode']);

        // Add this plugin to the template paths.
        add_filter('edd_template_paths', [$this, 'add_template_path']);

        // Move error messages area
        // remove_action('edd_purchase_form_before_submit', 'edd_print_errors');
        // add_action('edd_before_purchase_form', 'edd_print_errors');
        // remove_action('edd_ajax_checkout_errors', 'edd_print_errors');
        // add_action('edd_before_purchase_form', 'edd_print_errors');
        // remove_action('edd_after_cc_fields', 'edds_add_stripe_errors', 999);
        // add_action('edd_before_purchase_form', 'edds_add_stripe_errors', 999);

        // Modify Text Fields
        add_filter('gettext_easy-digital-downloads', [$this, 'modify_text_fields'], 1, 3);
        add_filter('gettext_edd-eu-vat', [$this, 'modify_text_fields'], 1, 3);

        // Move Login Form
        remove_action('edd_purchase_form_login_fields', 'edd_get_login_fields');
        add_action('edd_checkout_form_top', 'edd_get_login_fields', -2);

        // Move User Info (Email, First and Last Name)
        remove_action('edd_purchase_form_after_user_info', 'edd_user_info_fields');
        add_action('edd_checkout_form_top', 'edd_user_info_fields');

        // Move Billing Details
        remove_action('edd_after_cc_fields', 'edd_default_cc_address_fields');
        remove_action('edd_purchase_form_after_cc_form', 'edd_checkout_tax_fields', 999);
        add_action('edd_checkout_form_top', 'edd_default_cc_address_fields');

        // Move Discount Form
        remove_action('edd_checkout_form_top', 'edd_discount_field', -1);
        add_action('edd_after_checkout_cart', 'edd_discount_field', -1);

        // Stylesheet
        add_action('wp_footer', [$this, 'add_inline_stylesheet']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts_and_styles']);
    }

    /**
     * 
     * @return void 
     */
    public function replace_shortcode()
    {
        remove_shortcode('download_checkout', 'edd_checkout_form_shortcode');
        add_shortcode('download_checkout', [$this, 'edd_checkout_form']);
    }

    /**
     * Add this plugin's edd_templates folder as a file path for template files.
     * @see edd_get_theme_template_paths()
     * 
     * @param mixed $template_paths 
     * @return mixed 
     */
    public function add_template_path($template_paths)
    {
        return [5 => $this->plugin_dir . 'edd_templates/'] + $template_paths;
    }

    /**
     * Modifies lines for a few input fields.
     * 
     * @param mixed $translation 
     * @param mixed $text 
     * @param mixed $domain 
     * @return mixed 
     */
    public function modify_text_fields($translation, $text, $domain)
    {
        if (in_array($text, self::FFWP_BETTER_CHECKOUT_REWRITE_TEXT_FIELDS)) {
            return array_search($text, self::FFWP_BETTER_CHECKOUT_REWRITE_TEXT_FIELDS);
        }

        return $translation;
    }

    /**
     * Get Checkout Form
     *
     * @see easy-digital-downloads/includes/checkout/template.php::edd_checkout_form()
     * @return string
     */
    public function edd_checkout_form()
    {
        $payment_mode = edd_get_chosen_gateway();
        $form_action  = esc_url(edd_get_checkout_uri('payment-mode=' . $payment_mode));

        ob_start(); ?>
        <div id="edd_checkout_wrap">
            <?php if (edd_get_cart_contents() || edd_cart_has_fees()) : ?>
                <div id="ffwpress-payment-details__wrapper">
                    <?php do_action('edd_before_purchase_form'); ?>
                </div>
                <div id="ffwpress-cart__wrapper"></div>
                <div id="ffwpress-payment-details__wrapper">
                    <div id="edd_checkout_form_wrap" class="edd_clearfix">
                        <form id="edd_purchase_form" class="edd_form" action="<?php echo $form_action; ?>" method="POST">
                            <?php
                            /**
                             * Hooks in at the top of the checkout form
                             *
                             * @since 1.0
                             */
                            do_action('edd_checkout_form_top');

                            if (edd_is_ajax_disabled() && !empty($_REQUEST['payment-mode'])) {
                                do_action('edd_purchase_form');
                            } elseif (edd_show_gateways()) {
                                do_action('edd_payment_mode_select');
                            } else {
                                do_action('edd_purchase_form');
                            }

                            /**
                             * Hooks in at the bottom of the checkout form
                             *
                             * @since 1.0
                             */
                            do_action('edd_checkout_form_bottom')
                            ?>
                            <input type="submit" class="edd-apply-discount edd-submit blue button hidden" value="Apply" />
                        </form>
                        <?php do_action('edd_after_purchase_form'); ?>
                    </div>
                    <!--end #edd_checkout_form_wrap-->
                </div>
                <div id="ffwpress-cart__wrapper">
                    <fieldset id="ffwpress_checkout_shopping_cart">
                        <legend><?= __('Your Shopping Cart', 'easy-digital-downloads'); ?></legend>
                        <?php edd_checkout_cart(); ?>
                    </fieldset>
                </div>
            <?php
            else :
                /**
                 * Fires off when there is nothing in the cart
                 *
                 * @since 1.0
                 */
                do_action('edd_cart_empty');
            endif; ?>
        </div>
        <!--end #edd_checkout_wrap-->
    <?php
        return ob_get_clean();
    }

    public function enqueue_scripts_and_styles()
    {
        if (!edd_is_checkout()) {
            return;
        }

        wp_enqueue_style('ffwpress-icons', FFWP_PLUGIN_URL . 'assets/css/ffwpress-icons.css');
    }

    /**
     *
     */
    public function add_inline_stylesheet()
    {
        if (!edd_is_checkout()) {
            return;
        }
    ?>
        <script>
            jQuery(document).ready(function($) {
                var ffwp_checkout = {
                    init: function() {
                        $(document.body).on('edd_taxes_recalculated', this.addClass);

                        /**
                         * Since the discount form is moved outside the form, we're triggering a click on 
                         * a hidden input field inside the form.
                         */
                        $('#ffwpress_checkout_shopping_cart .edd-apply-discount').on('click', function() {
                            $('#edd_checkout_form_wrap .edd-apply-discount').click()
                        });

                    },

                    addClass: function() {
                        var $result_data = $('#edd-vat-check-result').data('valid');
                        var $validate_button = $('#edd-vat-check-button');

                        if ($result_data === 1) {
                            $validate_button.addClass('ffwp-vat-valid');
                            $validate_button.val('Valid');
                        } else {
                            $validate_button.removeClass('ffwp-vat-valid');
                            $validate_button.val('Validate');
                        }
                    }
                };

                ffwp_checkout.init();
            });
        </script>

        <style>
            <?php
            /**
             * General
             */
            ?>.edd-checkout.ast-separate-container article.ast-article-single {
                padding: 0;
            }

            .edd-checkout #edd_checkout_wrap {
                max-width: 1200px;
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                margin: 0;
            }

            #edd_checkout_form_wrap fieldset {
                border: 0;
            }

            #ffwpress-payment-details__wrapper {
                background-color: transparent;
                padding: 0;
                width: 66%;
            }

            #ffwpress-cart__wrapper {
                width: 34%;
                background: #0daadb;
                padding: 15px 30px 30px;
            }

            #edd_purchase_form {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
            }

            <?php
            /**
             * Notification area
             */
            ?>.edd-alert {
                margin: 0 30px;
                padding: 10px 20px;
            }

            #edd_checkout_wrap p.edd_error {
                padding: 0;
            }

            #edd_checkout_wrap fieldset#edd_sl_renewal_fields {
                border: 0;
                background: #0daadb;
                color: white;
                border-radius: 3px;
                margin: 20px 30px 10px;
                padding: 10px 20px 5px;
            }

            #edd_sl_renewal_fields p {
                padding-left: 0;
            }

            #edd_checkout_wrap fieldset#edd_sl_renewal_fields a {
                color: white;
                text-decoration: underline;
            }

            .edd-sl-renewal-actions {
                margin: 15px 0 0;
            }

            #edd_sl_renewal_fields .edd-submit {
                background-color: #2ECC40;
            }

            <?php
            /**
             * Personal Info and Billing Address
             */
            ?>#ffwpress-payment-details__wrapper fieldset legend,
            #ffwpress-cart__wrapper fieldset legend {
                color: #0daadb;
                background-color: transparent;
                border-bottom: 0;
            }

            #edd-first-name-wrap,
            #edd-last-name-wrap,
            #edd-card-address-wrap,
            #edd-card-address-2-wrap,
            #edd-card-zip-wrap,
            #edd-card-city-wrap,
            #edd-card-state-wrap,
            #edd-card-country-wrap,
            #edd_final_total_wrap {
                width: 48%;
                display: inline-block;
                margin-right: 0;
                padding-right: 0 !important;
            }

            .edd-input,
            .edd-select {
                border-radius: 3px !important;
                padding: 15px 20px !important;
                width: 100%;
            }

            <?php
            /**
             * EU VAT
             */
            ?>.edd-vat-number-wrap {
                width: 48%;
                position: relative;
            }

            #edd-vat-number {
                width: 100%;
            }

            #edd-card-vat-wrap label {
                display: block;
            }

            #edd-vat-check-button.ffwp-vat-valid {
                background-color: #2ECC40;
            }

            #edd-card-vat-wrap .edd-loading-ajax.edd-loading {
                position: absolute;
                top: 15%;
                left: 100%;
            }


            <?php
            /**
             * Payment Details
             */
            ?>#edd_checkout_form_wrap .edd-description,
            #edd_cc_address legend,
            #edd_cc_fields legend,
            #edd_sl_renewal_fields legend,
            #edd_secure_site_wrapper {
                display: none;
            }

            #edd_payment_mode_select_wrap {
                width: 100%;
            }

            #edd_payment_mode_select .ffwpress-secure-lock {
                float: right;
                font-weight: 400;
                color: #2ECC40;
            }

            fieldset#edd_checkout_user_info {
                margin: 0;
                width: 100%;
            }

            #edd_checkout_wrap #edd_discount_code {
                margin-bottom: 10px;
            }


            <?php
            /**
             * Payment Method
             */
            ?>#edd_checkout_form_wrap #edd-payment-mode-wrap label {
                margin-right: 29px;
            }

            #edd_checkout_form_wrap #edd-payment-mode-wrap label:last-child {
                margin-right: 0;
            }

            .edd-gateway-option {
                position: relative;
                width: 48%;
                padding: 15px 0 15px 15px;
                border: 1px #ddd solid;
                border-radius: 3px;
            }

            .edd-gateway-option:after {
                content: '';
                position: absolute;
                top: 50%;
                right: 15px;
                transform: translate(0, -50%);
                background-repeat: no-repeat;
                background-position: center;
                background-size: contain;
                height: 50%;
            }

            #edd-gateway-option-stripe:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/stripe-logo.png'; ?>');
                width: 143px;
            }

            #edd-gateway-option-paypal:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/paypal-logo.png'; ?>');
                width: 131px;
            }

            .edd-gateway-option-selected {
                border: 1px #0daadb solid;
            }

            <?php
            /**
             * Terms & Conditions
             */
            ?>#edd_terms_agreement {
                padding: 15px 0 10px !important;
            }

            #edd-jilt-marketing-consent,
            #edd_agree_to_terms {
                height: 1.2rem;
                width: 1.2rem;
            }

            <?php
            /**
             * Shopping Cart
             */
            ?>#ffwpress-cart__wrapper fieldset legend {
                color: white;
                padding: 0;
            }

            #edd_checkout_wrap fieldset#ffwpress_checkout_shopping_cart>div {
                padding: 0;
            }

            table#edd_checkout_cart,
            td,
            tr,
            th {
                color: white;
                border: 0;
            }

            #edd_checkout_cart td,
            #edd_checkout_cart th {
                padding: 10px 0;
            }

            #edd_checkout_wrap .edd_cart_remove_item_btn {
                color: white;
                margin-left: 0;
                font-size: .75em;
            }

            td.edd_cart_item_price,
            th.edd_cart_tax,
            th.edd_cart_subtotal {
                text-align: right;
            }

            .edd_cart_footer_row .edd_cart_total {
                border-top: 1px white solid;
            }

            <?php
            /**
             * Discount Code
             */
            ?>#edd_checkout_wrap #edd_discount_code {
                border: 0;
                color: white;
            }

            .edd_discount_link {
                color: white;
                text-decoration: underline;
            }

            <?php
            /**
             * Complete Order
             */
            ?>#edd_checkout_form_wrap #edd_final_total_wrap {
                width: 100%;
                text-align: center;
                font-size: 1.25rem;
                border: 0;
            }

            #edd-purchase-button {
                background-color: #2ECC40;
                width: 92.5%;
                font-size: 1.5rem;
                padding: 20px;
                margin: 0 30px;
            }

            #ffwpress_checkout_shopping_cart .edd-submit {
                background-color: #2ECC40;
            }

            .edd-apply-discount.hidden {
                display: none;
            }

            #edd-discount-error-wrap {
                display: block;
            }

            #edd-discount-error-wrap.edd-alert {
                margin: 15px 0;
            }


            @media only screen and (max-width: 480px) {

                #edd-first-name-wrap,
                #edd-last-name-wrap,
                #edd-card-address-wrap,
                #edd-card-address-2-wrap,
                #edd-card-zip-wrap,
                #edd-card-city-wrap,
                #edd-card-state-wrap,
                #edd-card-country-wrap,
                #edd-vat-number {
                    width: 91%;
                }

                #edd_checkout_cart td,
                #edd_checkout_cart th {
                    padding: 10px 25px;
                }

                #edd-purchase-button {
                    width: 100%;
                    margin-left: 0;
                }
            }
        </style>
<?php
    }
}
