<?php

/**
 * @package   FFWP Login Fields Legend
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_LoginFieldsLegend_Insert
{
    /** @var string $plugin_text_domain */
    private $plugin_text_domain = 'ffwp';

    /**
     * FFWP_LoginFieldsLegend_Insert constructor.
     */
    public function __construct()
    {
        add_action('edd_before_purchase_form', [$this, 'insert'], -2);
        add_action('wp_footer', [$this, 'add_stylesheet']);
        add_action('wp_footer', [$this, 'add_script']);
    }

    /**
     *
     */
    public function insert()
    {
        if (!is_user_logged_in()) : ?>
            <fieldset id="ffwp-account-form">
                <p>
                    <a href="#" id="ffwp-account-modal"><i class="icon-user"></i><?= __('Login to your account', $this->plugin_text_domain); ?></a>
                </p>
            </fieldset>
        <?php endif;
    }

    public function add_stylesheet()
    {
        if (!edd_is_checkout()) {
            return;
        }
        ?>
        <style>
            #ffwp-account-form {
                width: 100%;
                text-align: right;
            }

            #ffwp-account-form p {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
                padding-left: 30px;
                padding-right: 30px;
                margin-bottom: 0 !important;
                margin-top: 5px;
            }

            #edd_login_fields {
                position: fixed;
                right: 0;
                left: 0;
                padding-top: 15px !important;
                margin-left: auto !important;
                margin-right: auto !important;
                display: none;
                width: 66.66%;
                top: 33%;
                background-color: #fff;
                height: 25%;
                z-index: 10000;
                box-shadow: 3px 3px 25px #ccc;
            }

            #edd_login_fields .close {
                position: absolute;
                top: -1px;
                right: -1px;
                border: 1px solid #eee;
                font-weight: 700;
                font-size: 1.4rem;
                padding: 0 15px;
                cursor: pointer;
            }
        </style>
    <?php
    }

    public function add_script()
    {
        if (!edd_is_checkout()) {
            return;
        }
    ?>
        <script>
            jQuery(document).ready(function($) {
                var account_modal = {
                    $login_fields: $('#edd_login_fields'),

                    init: function() {
                        account_modal.$login_fields.append('<div class="close">×</div>');

                        $('#ffwp-account-modal').on('click', this.open_modal);
                        $('#edd_login_fields .close').on('click', this.close);
                    },

                    open_modal: function(e) {
                        account_modal.$login_fields.css({
                            display: 'initial'
                        });
                    },

                    close: function(e) {
                        account_modal.$login_fields.css({
                            display: 'none'
                        });
                    }
                }

                account_modal.init();
            });
        </script>
<?php
    }
}
