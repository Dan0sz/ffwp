<?php
/**
 * @package   FFWP/WooshNonRequiredStateField
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_NonRequiredStateField_Set
{
    /**
     * WooshNonRequiredStateField constructor.
     */
    public function __construct()
    {
        add_filter('edd_purchase_form_required_fields', [$this, 'remove_state_from_required_fields']);
        add_action('edd_cc_billing_bottom', [$this, 'insert_script']);
    }

    /**
     * @param $fields
     *
     * @return array
     */
    public function remove_state_from_required_fields($fields)
    {
        if (array_key_exists('card_state', $fields)) {
            unset($fields['card_state']);
        }

        return $fields;
    }

    public function insert_script()
    {
        ?>
        <script>
            jQuery(document).ready(function ($) {
                var ffwp = {
                    $card_state_label: $('#edd-card-state-wrap label'),
                    required_countries: [
                        'US', 'AO', 'CA', 'AU', 'BD', 'BG', 'BR', 'CN', 'GB', 'HK', 'HU', 'ID', 'IN', 'IR', 'IT', 'JP', 'MX', 'MY', 'NP', 'NZ', 'PE', 'TH', 'TR', 'ZA', 'ES'
                    ],

                    init: function () {
                        $(document.body).on('edd_cart_billing_address_updated', this.is_required);
                        this.is_required();
                    },

                    is_required: function () {
                        var $billing_country = $('#billing_country').val();

                        if (ffwp.required_countries.includes($billing_country)) {
                            ffwp.$card_state_label.append('<span class="edd-required-indicator">*</span>');
                            $('#card_state').attr('required', 'required');
                        } else {
                            $('#edd-card-state-wrap label .edd-required-indicator').remove();
                        }
                    }
                };

                ffwp.init();
            });
        </script>
        <?php
    }
}
