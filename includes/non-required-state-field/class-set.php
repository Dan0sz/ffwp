<?php

/**
 * @package   FFWP/WooshNonRequiredStateField
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_NonRequiredStateField_Set
{
    /**
     * Contains all country codes for countries with a predefined list of states.
     *
     * @var array $required_countries
     * @see edd_get_shop_states()
     * @since v1.3.0
     */
    private $required_countries = [
        'US', 'AO', 'CA', 'AU', 'BD', 'BG', 'BR', 'CN', 'GB', 'HK', 'HU', 'ID', 'IN', 'IR', 'IT', 'JP', 'MX', 'MY', 'NP', 'NZ', 'PE', 'TH', 'TR', 'ZA', 'ES'
    ];

    /**
     * FFWP_NonRequiredStateField_Set constructor.
     */
    public function __construct()
    {
        add_filter('edd_purchase_form_required_fields', [$this, 'remove_state_from_required_fields']);
        add_action('wp_footer', [$this, 'insert_script']);
    }

    /**
     * @param $fields
     *
     * @return array
     */
    public function remove_state_from_required_fields($fields)
    {
        $country = sanitize_text_field($_POST['billing_country'] ?? '');

        if (in_array($country, $this->required_countries)) {
            return $fields;
        }

        if (array_key_exists('card_state', $fields)) {
            unset($fields['card_state']);
        }

        return $fields;
    }

    /**
     *
     */
    public function insert_script()
    {
        if (!edd_is_checkout()) {
            return;
        }
?>
        <script>
            jQuery(document).ready(function($) {
                var ffwp = {
                    required_countries: <?= json_encode($this->required_countries); ?>,

                    /**
                     * ALL SYSTEMS GO!
                     */
                    init: function() {
                        $(document.body).on('change', '#billing_country', this.set_loader);
                        $(document.body).on('edd_cart_billing_address_updated', this.is_required);
                        this.is_required();
                    },

                    /**
                     * Set a loader to indicate that the state input is about to change.
                     */
                    set_loader: function() {
                        var $card_state_wrap = $('#edd-card-state-wrap');

                        $card_state_wrap.append('<span class="ffwp-loader edd-loading-ajax edd-loading"></span>');
                        $card_state_wrap.css({
                            opacity: 0.5
                        });
                    },

                    /**
                     * Set or remove asterisk and remove loader.
                     */
                    is_required: function() {
                        var $billing_country = $('#billing_country').val();
                        var $required_indicator = $('#edd-card-state-wrap label .edd-required-indicator');
                        var $card_state_wrap = $('#edd-card-state-wrap');
                        var $card_state_label = $('#edd-card-state-wrap label');


                        if (ffwp.required_countries.includes($billing_country)) {
                            if ($required_indicator.length === 0) {
                                $card_state_label.append('<span class="edd-required-indicator">*</span>');
                            }
                        } else {
                            $required_indicator.remove();
                        }

                        $('#edd-card-state-wrap .ffwp-loader').remove();
                        $card_state_wrap.css({
                            opacity: 1
                        });
                    }
                };

                ffwp.init();
            });
        </script>
        <style>
            #edd-card-state-wrap {
                position: relative;
            }

            #edd-card-state-wrap .ffwp-loader {
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                margin-left: auto;
                margin-right: auto;
            }
        </style>
<?php
    }
}
