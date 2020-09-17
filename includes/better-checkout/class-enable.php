<?php
/**
 * @package   FFWP Better Checkout
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_BetterCheckout_Enable
{
    /**
     * FFWP_BetterCheckout_Enable constructor.
     */
    public function __construct()
    {
        // @formatter:off
        // Remove default User Info Fields
        remove_action('edd_purchase_form_after_user_info', 'edd_user_info_fields');
        remove_action('edd_register_fields_before', 'edd_user_info_fields');

        // User Info Fields
        add_action('edd_checkout_form_top', [ $this, 'set_user_info_fields' ]);
        add_action('edd_register_fields_before', [ $this, 'set_user_info_fields' ]);

        // Remove Payment Methods
        remove_action('edd_payment_mode_select', 'edd_payment_mode_select');

        // Payment Methods
        add_action('edd_payment_mode_select', [ $this, 'set_payment_methods' ]);


        /**
         * TODO: Remove and add better Credit Card Fields
         */

        // Remove default Billing Fields
        remove_action('edd_after_cc_fields', 'edd_default_cc_address_fields');
        remove_action('edd_purchase_form_after_cc_form', 'edd_checkout_tax_fields',999);

        // Billing Fields
        add_action('edd_after_cc_fields', [ $this, 'set_billing_fields' ]);
        add_action('edd_purchase_form_after_cc_form', [ $this, 'maybe_set_billing_fields' ]);

        // EU VAT Fields
        add_filter('edd_vat_checkout_vat_field_html', [ $this, 'set_eu_vat_fields' ], 10, 3);

        // Stylesheet
        add_action('wp_footer', [ $this, 'add_inline_stylesheet' ]);
        // @formatter:on
    }

    /**
     *
     */
    public function set_user_info_fields()
    {
        $customer = EDD()->session->get('customer');
        $customer = wp_parse_args(
            $customer,
            array(
                'first_name' => '',
                'last_name'  => '',
                'email'      => ''
            )
        );

        if (is_user_logged_in()) {
            $user_data = get_userdata(get_current_user_id());
            foreach ($customer as $key => $field) {

                if ('email' == $key && empty($field)) {
                    $customer[$key] = $user_data->user_email;
                } elseif (empty($field)) {
                    $customer[$key] = $user_data->$key;
                }
            }
        }

        $customer = array_map('sanitize_text_field', $customer);
        ?>
        <fieldset id="edd_checkout_user_info">
            <legend><?php echo apply_filters('edd_checkout_personal_info_text', esc_html__('Personal Info', 'easy-digital-downloads')); ?></legend>
            <?php do_action('edd_purchase_form_before_email'); ?>
            <p id="edd-email-wrap">
                <label class="edd-label" for="edd-email">
                    <?php esc_html_e('Email Address', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('edd_email')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <input class="edd-input required" type="email" name="edd_email" placeholder="<?php esc_html_e('Email address', 'easy-digital-downloads'); ?>" id="edd-email" value="<?php echo esc_attr($customer['email']); ?>" aria-describedby="edd-email-description"<?php if (edd_field_is_required('edd_email')) {
                    echo ' required ';
                } ?>/>
            </p>
            <?php do_action('edd_purchase_form_after_email'); ?>
            <p id="edd-first-name-wrap">
                <label class="edd-label" for="edd-first">
                    <?php esc_html_e('First Name', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('edd_first')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <input class="edd-input required" type="text" name="edd_first" placeholder="<?php esc_html_e('First Name', 'easy-digital-downloads'); ?>" id="edd-first" value="<?php echo esc_attr($customer['first_name']); ?>"<?php if (edd_field_is_required('edd_first')) {
                    echo ' required ';
                } ?> aria-describedby="edd-first-description"/>
            </p>
            <p id="edd-last-name-wrap">
                <label class="edd-label" for="edd-last">
                    <?php esc_html_e('Last Name', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('edd_last')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <input class="edd-input<?php if (edd_field_is_required('edd_last')) {
                    echo ' required';
                } ?>" type="text" name="edd_last" id="edd-last" placeholder="<?php esc_html_e('Last Name', 'easy-digital-downloads'); ?>" value="<?php echo esc_attr($customer['last_name']); ?>"<?php if (edd_field_is_required('edd_last')) {
                    echo ' required ';
                } ?> aria-describedby="edd-last-description"/>
            </p>
            <?php do_action('edd_purchase_form_user_info'); ?>
            <?php do_action('edd_purchase_form_user_info_fields'); ?>
        </fieldset>
        <?php
    }

    /**
     *
     */
    public function set_payment_methods()
    {
        $gateways       = edd_get_enabled_payment_gateways(true);
        $page_URL       = edd_get_current_page_url();
        $chosen_gateway = edd_get_chosen_gateway();
        ?>
        <div id="edd_payment_mode_select_wrap">
            <?php do_action('edd_payment_mode_top'); ?>
            <?php if (edd_is_ajax_disabled()) { ?>
            <form id="edd_payment_mode" action="<?php echo $page_URL; ?>" method="GET">
                <?php } ?>
                <fieldset id="edd_payment_mode_select">
                    <legend><?php _e('Select Payment Method', 'easy-digital-downloads'); ?></legend>
                    <?php do_action('edd_payment_mode_before_gateways_wrap'); ?>
                    <div id="edd-payment-mode-wrap">
                        <?php

                        do_action('edd_payment_mode_before_gateways');

                        $i = 0;

                        foreach ($gateways as $gateway_id => $gateway) :
                            if ($i % 2 == 0) {
                                echo '<div class="edd-gateway-column left">';
                            } else {
                                echo '<div class="edd-gateway-column right">';
                            }

                            $label         = apply_filters('edd_gateway_checkout_label_' . $gateway_id, $gateway['checkout_label']);
                            $checked       = checked($gateway_id, $chosen_gateway, false);
                            $checked_class = $checked ? ' edd-gateway-option-selected' : '';
                            $nonce         = ' data-' . esc_attr($gateway_id) . '-nonce="' . wp_create_nonce('edd-gateway-selected-' . esc_attr($gateway_id)) . '"';

                            echo '<label for="edd-gateway-' . esc_attr($gateway_id) . '" class="edd-gateway-option' . $checked_class . '" id="edd-gateway-option-' . esc_attr($gateway_id) . '">';
                            echo '<input type="radio" name="payment-mode" class="edd-gateway" id="edd-gateway-' . esc_attr($gateway_id) . '" value="' . esc_attr($gateway_id) . '"' . $checked . $nonce . '>' . esc_html($label);
                            echo '</label>';

                            echo '</div>';

                            $i++;
                        endforeach;

                        do_action('edd_payment_mode_after_gateways');

                        ?>
                    </div>
                    <?php do_action('edd_payment_mode_after_gateways_wrap'); ?>
                </fieldset>
                <fieldset id="edd_payment_mode_submit" class="edd-no-js">
                    <p id="edd-next-submit-wrap">
                        <?php echo edd_checkout_button_next(); ?>
                    </p>
                </fieldset>
                <?php if (edd_is_ajax_disabled()) { ?>
            </form>
        <?php } ?>
        </div>
        <div id="edd_purchase_form_wrap"></div><!-- the checkout fields are loaded into this-->

        <?php do_action('edd_payment_mode_bottom');
    }

    /**
     *
     */
    public function set_billing_fields()
    {
        $logged_in = is_user_logged_in();
        $customer  = EDD()->session->get('customer');
        $customer  = wp_parse_args(
            $customer, array(
                'address' => array(
                    'line1'   => '',
                    'line2'   => '',
                    'city'    => '',
                    'zip'     => '',
                    'state'   => '',
                    'country' => ''
                )
            )
        );

        $customer['address'] = array_map('sanitize_text_field', $customer['address']);

        if ($logged_in) {

            $user_address = get_user_meta(get_current_user_id(), '_edd_user_address', true);

            foreach ($customer['address'] as $key => $field) {

                if (empty($field) && !empty($user_address[$key])) {
                    $customer['address'][$key] = $user_address[$key];
                } else {
                    $customer['address'][$key] = '';
                }

            }

        }

        /**
         * Billing Address Details.
         *
         * Allows filtering the customer address details that will be pre-populated on the checkout form.
         *
         * @param array $address  The customer address.
         * @param array $customer The customer data from the session
         *
         * @since 2.8
         *
         */
        $customer['address'] = apply_filters('edd_checkout_billing_details_address', $customer['address'], $customer);

        ob_start(); ?>
        <fieldset id="edd_cc_address" class="cc-address">
            <legend><?php _e('Billing Details', 'easy-digital-downloads'); ?></legend>
            <?php do_action('edd_cc_billing_top'); ?>
            <p id="edd-card-address-wrap">
                <label for="card_address" class="edd-label">
                    <?php _e('Street + House No.', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('card_address')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <input type="text" id="card_address" name="card_address" class="card-address edd-input<?php if (edd_field_is_required('card_address')) {
                    echo ' required';
                } ?>" value="<?php echo $customer['address']['line1']; ?>"<?php if (edd_field_is_required('card_address')) {
                    echo ' required ';
                } ?>/>
            </p>
            <p id="edd-card-address-2-wrap">
                <label for="card_address_2" class="edd-label">
                    <?php _e('Suite, Apt no., PO box, etc. (optional)', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('card_address_2')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <input type="text" id="card_address_2" name="card_address_2" class="card-address-2 edd-input<?php if (edd_field_is_required('card_address_2')) {
                    echo ' required';
                } ?>" value="<?php echo $customer['address']['line2']; ?>"<?php if (edd_field_is_required('card_address_2')) {
                    echo ' required ';
                } ?>/>
            </p>
            <p id="edd-card-zip-wrap">
                <label for="card_zip" class="edd-label">
                    <?php _e('Zip/Postal Code', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('card_zip')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <input type="text" size="4" id="card_zip" name="card_zip" class="card-zip edd-input<?php if (edd_field_is_required('card_zip')) {
                    echo ' required';
                } ?>" value="<?php echo $customer['address']['zip']; ?>"<?php if (edd_field_is_required('card_zip')) {
                    echo ' required ';
                } ?>/>
            </p>
            <p id="edd-card-city-wrap">
                <label for="card_city" class="edd-label">
                    <?php _e('City', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('card_city')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <input type="text" id="card_city" name="card_city" class="card-city edd-input<?php if (edd_field_is_required('card_city')) {
                    echo ' required';
                } ?>" value="<?php echo $customer['address']['city']; ?>"<?php if (edd_field_is_required('card_city')) {
                    echo ' required ';
                } ?>/>
            </p>
            <div id="edd-card-country-wrap">
                <label for="billing_country" class="edd-label">
                    <?php _e('Country', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('billing_country')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <div class="ffwp-select">
                    <select name="billing_country" id="billing_country" data-nonce="<?php echo wp_create_nonce('edd-country-field-nonce'); ?>" class="billing_country edd-select<?php if (edd_field_is_required('billing_country')) {
                        echo ' required';
                    } ?>"<?php if (edd_field_is_required('billing_country')) {
                        echo ' required ';
                    } ?>>
                        <?php
                        $selected_country = edd_get_shop_country();

                        if (!empty($customer['address']['country']) && '*' !== $customer['address']['country']) {
                            $selected_country = $customer['address']['country'];
                        }

                        $countries = edd_get_country_list();
                        foreach ($countries as $country_code => $country) {
                            echo '<option value="' . esc_attr($country_code) . '"' . selected($country_code, $selected_country, false) . '>' . $country . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div id="edd-card-state-wrap">
                <label for="card_state" class="edd-label">
                    <?php _e('State/Province', 'easy-digital-downloads'); ?>
                    <?php if (edd_field_is_required('card_state')) { ?>
                        <span class="edd-required-indicator">*</span>
                    <?php } ?>
                </label>
                <?php
                $selected_state = edd_get_shop_state();
                $states         = edd_get_shop_states($selected_country);

                if (!empty($customer['address']['state'])) {
                    $selected_state = $customer['address']['state'];
                }
                if (!empty($states)) : ?>
                    <div class="ffwp-select">
                        <select name="card_state" id="card_state" class="card_state edd-select<?php if (edd_field_is_required('card_state')) {
                            echo ' required';
                        } ?>">
                            <?php
                            foreach ($states as $state_code => $state) {
                                echo '<option value="' . $state_code . '"' . selected($state_code, $selected_state, false) . '>' . $state . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                <?php else : ?>
                    <?php $customer_state = !empty($customer['address']['state']) ? $customer['address']['state'] : ''; ?>
                    <input type="text" size="6" name="card_state" id="card_state" class="card_state edd-input" value="<?php echo esc_attr($customer_state); ?>"/>
                <?php endif; ?>
            </div>
            <?php do_action('edd_cc_billing_bottom'); ?>
            <?php wp_nonce_field('edd-checkout-address-fields', 'edd-checkout-address-fields-nonce', false, true); ?>
        </fieldset>
        <?php
        echo ob_get_clean();
    }

    /**
     *
     */
    public function maybe_set_billing_fields()
    {
        if (edd_cart_needs_tax_address_fields() && edd_get_cart_total()) {
            $this->set_billing_fields();
        }
    }

    /**
     * @param $html
     * @param $details
     * @param $vat_reverse_charged
     */
    public function set_eu_vat_fields($html, $details, $vat_reverse_charged)
    {
        ?>
        <p id="edd-card-vat-wrap">
            <label for="edd-vat-number" class="edd-label"><?= __('VAT Number', 'easy-digital-downloads'); ?></label>
            <span class="edd-vat-number-wrap">
                <input type="text" name="vat_number" id="edd-vat-number" class="edd-input edd-vat-number-input" value="<?= $details->vat_number ?? ''; ?>" placeholder="e.g. GB123456789"/>
                <input type="button" name="edd-vat-check" id="edd-vat-check-button" class="button edd-vat-check-button <?= $vat_reverse_charged ? 'ffwp-vat-valid' : ''; ?>" value="<?= $vat_reverse_charged ? __('Valid', 'easy-digital-downloads') : __('Validate', 'easy-digital-downloads'); ?>"/>
            </span>
        </p>
        <?php
    }

    /**
     *
     */
    public function add_inline_stylesheet()
    {
        ?>
        <script>
            jQuery(document).ready(function ($) {
                var ffwp_checkout = {
                    init: function () {
                        $(document.body).on('edd_taxes_recalculated', this.addClass);
                    },

                    addClass: function () {
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
             * Payment Methods
             */
             ?>
            .edd-gateway-column {
                float: left;
                width: 48%;
                padding: 15px 20px 10px;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }

            .edd-gateway-column.left {
                clear: left;
            }

            .edd-gateway-column.right {
                clear: right;
                margin-left: 30px;
            }

            #edd_checkout_form_wrap #edd-payment-mode-wrap label {
                display: block;
                position: relative;
            }

            #edd_checkout_form_wrap #edd-payment-mode-wrap label:after {
                position: absolute;
                left: 0;
                background-size: 36px;
                width: 36px;
                height: 26px;
                content: '';
                display: inline-block;
            }

            .edd-gateway {
                height: 1.4rem;
                width: 1.4rem;
                margin-top: -5px;
                padding-left: 3rem;
            }

            .edd-gateway-option {
                padding: 0 0 0 40px;
            }

            #edd-gateway-option-paypal:after {
                background-image: url('/wp-content/plugins/easy-digital-downloads/templates/images/icons/paypal.png');
            }

            #edd-gateway-option-mollie_bancontact:after {
                background-image: url('/wp-content/plugins/edd-mollie-gateway/assets/images/bancontact.svg');
            }

            #edd-gateway-option-mollie_creditcard:after {
                background-image: url('/wp-content/plugins/edd-mollie-gateway/assets/images/creditcard.svg');
            }

            #edd-gateway-option-mollie_ideal:after {
                background-image: url('/wp-content/plugins/edd-mollie-gateway/assets/images/ideal.svg');
            }

            #edd-gateway-option-mollie_sofort:after {
                background-image: url('/wp-content/plugins/edd-mollie-gateway/assets/images/sofort.svg');
            }

            <?php
            /**
             * Personal Info and Billing Address
             */
            ?>
            #edd-first-name-wrap,
            #edd-last-name-wrap,
            #edd-card-address-wrap,
            #edd-card-address-2-wrap,
            #edd-card-zip-wrap,
            #edd-card-city-wrap,
            #edd-card-state-wrap,
            #edd-card-country-wrap,
            #edd-vat-number,
            #edd_final_total_wrap {
                width: 48%;
                display: inline-block;
                margin-right: 0;
                padding-right: 0 !important;
            }

            .edd-label,
            #edd-payment-mode-wrap label {
                font-size: 1.15rem;
            }

            .edd-input,
            .edd-select {
                font-size: 1.15rem;
                font-weight: 400;
                border-radius: 3px !important;
                padding: 15px 20px !important;
            }

            #billing_country {
                -webkit-appearance: none;
                -moz-appearance: none;
                padding-right: 3rem !important;
            }

            .ffwp-select {
                position: relative;
            }

            .ffwp-select:after {
                font-family: 'Astra';
                content: "\e900";
                position: absolute;
                right: 0;
                top: 0;
                pointer-events: none;
                height: 100%;
                background-color: #dae1e6;
                display: -webkit-box;
                display: flex;
                -webkit-box-pack: center;
                justify-content: center;
                -webkit-box-align: center;
                align-items: center;
                width: 3rem;
                z-index: -1;
            }

            <?php
            /**
             * EU VAT
             */
            ?>
            #edd-card-vat-wrap label {
                display: block;
            }

            #edd-vat-check-button {
                font-size: 1.4rem;
            }

            #edd-vat-check-button {
                font-size: 1.4rem;
                padding: 15px 30px;
                margin-left: 30px;
            }

            #edd-vat-check-button.ffwp-vat-valid {
                background-color: #2ECC40;
            }

            <?php
            /**
             * Terms & Conditions
             */
            ?>
            #edd_terms_agreement {
                padding: 15px 0 10px !important;
            }

            #edd_agree_to_terms {
                height: 1.4rem;
                width: 1.4rem;
            }

            #edd_checkout_form_wrap #edd_purchase_submit label {
                font-size: 1.15rem;
            }

            <?php
            /**
             * Totals & Purchase
             */
            ?>
            #edd_final_total_wrap {
                font-size: 1.4rem;
            }

            #edd-purchase-button {
                width: 48%;
                font-size: 1.4rem;
                padding: 20px;
                margin-left: 30px;
            }
        </style>
        <?php
    }
}
