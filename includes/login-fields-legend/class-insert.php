<?php
/**
 * @package   FFWP Login Fields Legend
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined( 'ABSPATH' ) || exit;

class FFWP_LoginFieldsLegend_Insert {
	/** @var string $plugin_text_domain */
	private $plugin_text_domain = 'ffwp';

	/**
	 * FFWP_LoginFieldsLegend_Insert constructor.
	 */
	public function __construct() {
		// @formatter:off
        add_action('edd_checkout_form_top', [$this, 'insert']);
        add_action('wp_footer', [$this, 'add_stylesheet']);
        add_action('wp_footer', [$this, 'add_script']);
        // @formatter:on
	}

	/**
	 *
	 */
	public function insert() {
		?>
        <fieldset id="ffwp-account-form">
            <p>
				<?= __( 'Have an account?', $this->plugin_text_domain ); ?> <a href="#" id="ffwp-account-modal"><?= __('Click here to login', $this->plugin_text_domain); ?></a>
            </p>
        </fieldset>
		<?php
	}

	public function add_stylesheet() {
		?>
        <style>
            #ffwp-account-form p {
                padding-top: 10px !important;
                padding-bottom: 5px !important;
                padding-left: 30px;
                padding-right: 30px;
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
        ?>
        <script>
            jQuery(document).ready(function ($) {
                var account_modal = {
                    $login_fields: $('#edd_login_fields'),

                    init: function () {
                        account_modal.$login_fields.append('<div class="close">×</div>');

                        $('#ffwp-account-modal').on('click', this.open_modal);
                        $('#edd_login_fields .close').on('click', this.close);
                    },

                    open_modal: function (e) {
                        account_modal.$login_fields.css({ display: 'initial' });
                    },

                    close: function (e) {
                        account_modal.$login_fields.css({ display: 'none' });
                    }
                }

                account_modal.init();
            });
        </script>
        <?php
    }
}