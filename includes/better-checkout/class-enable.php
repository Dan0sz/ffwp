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
    const FFWP_BETTER_CHECKOUT_STATIC_VERSION = '1.0.0';

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

    private $plugin_url = '';

    /**
     * FFWP_BetterCheckout_Enable constructor.
     */
    public function __construct()
    {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

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
        wp_enqueue_script('ffwpress-better-checkout', $this->plugin_url . 'assets/js/better-checkout.js', ['jquery', 'edd-checkout-global'], self::FFWP_BETTER_CHECKOUT_STATIC_VERSION, true);
        wp_enqueue_style('ffwpress-better-checkout', $this->plugin_url . 'assets/css/better-checkout.css', ['astra-child-theme-css', 'edd-blocks', 'edd-eu-vat', 'edd-sl-styles'], self::FFWP_BETTER_CHECKOUT_STATIC_VERSION);
        wp_add_inline_style('ffwpress-better-checkout', $this->add_inline_stylesheet());
    }

    /**
     *
     */
    public function add_inline_stylesheet()
    {
        ob_start();
    ?>
        <style>
            #edd-gateway-option-stripe:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/stripe-logo.png'; ?>');
                width: 143px;
            }

            #edd-gateway-option-paypal:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/paypal-logo.png'; ?>');
                width: 131px;
            }
        </style>
<?php
        return str_replace(['<style>', '</style>'], '', ob_get_clean());
    }
}
