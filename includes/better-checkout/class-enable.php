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
        'City'         => 'Billing City',
        'Country'      => 'Billing Country',
        'Enter a valid VAT number to reverse charge EU VAT.' => 'Enter the VAT number of your company.',
        'Name on Card'                                       => 'Name on the Card',
        'Payment'                                            => 'Select Payment Method',
        ''                             => 'Excluding %1$s&#37; tax',
        'State/Province'               => 'Billing State / Province',
        'Street + House No.'           => 'Billing Address',
        'Suite, Apt No., PO Box, etc.' => 'Billing Address Line 2 (optional)',
        'Validate'                     => 'Check',
        'Zip/Postal Code'              => 'Billing Zip / Postal Code'
    ];

    private $plugin_dir = '';

    private $plugin_url = '';

    private $gateways = [];

    private $plugin_text_domain = 'ffwp';

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

        add_action('edd_purchase_link_top', [$this, 'add_vat_notice']);

        // Modify Text Fields
        add_filter('edd_checkout_personal_info_text', function () {
            return __('Your Details', $this->plugin_text_domain);
        });
        add_filter('gettext_easy-digital-downloads', [$this, 'modify_text_fields'], 1, 3);
        add_filter('gettext_edd-eu-vat', [$this, 'modify_text_fields'], 1, 3);
        add_filter('gettext_edds', [$this, 'modify_text_fields'], 1, 3);

        // Move Login Form
        remove_action('edd_purchase_form_login_fields', 'edd_get_login_fields');
        add_action('edd_checkout_form_top', 'edd_get_login_fields', -2);

        // Move cart messages
        remove_action('edd_before_checkout_cart', 'edd_display_cart_messages');
        add_action('edd_before_purchase_form', 'edd_display_cart_messages');

        // Move User Info (Email, First and Last Name)
        remove_action('edd_purchase_form_after_user_info', 'edd_user_info_fields');
        add_action('edd_checkout_form_top', 'edd_user_info_fields');

        // Move Billing Details
        remove_action('edd_after_cc_fields', 'edd_default_cc_address_fields');
        remove_action('edd_purchase_form_after_cc_form', 'edd_checkout_tax_fields', 999);
        add_action('edd_checkout_form_top', 'edd_checkout_tax_fields', 999);

        // Handle PayPal notices in checkout
        add_action('edd_purchase_form_before_submit', [$this, 'show_paypal_notice'], -1);
        add_action('wp_ajax_ffwp_maybe_remove_recurring_notice', array($this, 'maybe_remove_recurring_notice'));
        add_action('wp_ajax_nopriv_ffwp_maybe_remove_recurring_notice', array($this, 'maybe_remove_recurring_notice'));

        /**
         * When Taxes > 'Display Tax Rate' is enabled in EDD's settings, remove the mention for each
         * shopping cart item, because it seems excessive.
         */
        add_filter('edd_cart_item_tax_description', '__return_empty_string');

        // Move Discount Form
        remove_action('edd_checkout_form_top', 'edd_discount_field', -1);
        add_action('edd_after_checkout_cart', 'edd_discount_field', -1);

        // Modify required fields
        add_filter('edd_purchase_form_required_fields', [$this, 'add_required_fields']);

        // Stylesheet
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts_and_styles']);

        // Force available gateways
        add_filter('edd_enabled_payment_gateways', [$this, 'force_gateways'], 10000, 1);
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
                <div id="ffwpress-payment-details__wrapper" class="notification-area">
                    <?php do_action('edd_before_purchase_form'); ?>
                </div>
                <div id="ffwpress-cart__wrapper" class="cart-wrapper-mobile">
                    <a href="#edd_checkout_cart_form" class="ffwpress-cart-link hide-on-desktop"><?= __('View Shopping Cart'); ?></a>
                </div>
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

                            /**
                             * This hidden emulates clicks on the 'Apply Discount' button outside the form.
                             */
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

    /**
     * 
     */
    public function show_paypal_notice()
    {
        $selected_gateway = edd_get_chosen_gateway();

        if ($selected_gateway == 'mollie_paypal') : ?>
            <fieldset id="paypal-notice">
                <div class="paypal-notice">
                    <label>
                        <?= __('Licenses purchased with PayPal are not automatically billed and renewed. Your license can be renewed anytime from within your account area. You will be timely notified by email before your license expires.', $this->plugin_text_domain); ?>
                    </label>
                </div>
            </fieldset>
        <?php endif;
    }

    /**
     * Checks if reccuring notice should be removed based on selected gateway.
     */
    public function maybe_remove_recurring_notice()
    {
        if (empty($_REQUEST['action']) && $_REQUEST['action'] != 'ffwp_maybe_remove_recurring_notice' && empty($_REQUEST['gateway'])) {
            edd_die();
        }

        $gateway = $_REQUEST['gateway'];

        if ($gateway == 'mollie_paypal') {
            add_filter('edd_recurring_cart_item_notice', '__return_empty_string');
        }

        ob_start();

        edd_checkout_cart();

        $cart = ob_get_contents();

        ob_end_clean();

        $response = array(
            'html'  => $cart,
            'total' => html_entity_decode(edd_cart_total(false), ENT_COMPAT, 'UTF-8'),
        );

        echo json_encode($response);

        edd_die();
    }

    /**
     * Insert custom VAT notice above 'Add to cart' button
     * 
     * @return void 
     */
    public function add_vat_notice()
    {
        ?>
        <span class="edd_purchase_tax_rate">
            <?= __('<strong>+ VAT</strong> for EU residents', 'ffwp'); ?>
        </span>
    <?php
    }

    /**
     * Add Last Name and Street + House No. as required field, because it's dumb not to ask that.
     * 
     * @param mixed $required_fields 
     * @return mixed 
     */
    public function add_required_fields($required_fields)
    {
        if (edd_cart_needs_tax_address_fields() && edd_get_cart_total()) {
            $required_fields['edd_last'] = [
                'error_id' => 'invalid_last_name',
                'error_message' => 'Please enter your last name'
            ];
            $required_fields['card_address'] = [
                'error_id' => 'invalid_card_address',
                'error_message' => 'Please enter your Street + House no.'
            ];
        }

        return $required_fields;
    }

    /**
     * Enqueue scripts and styles.
     * 
     * @return void 
     */
    public function enqueue_scripts_and_styles()
    {
        $suffix = $this->get_script_suffix();

        wp_enqueue_style('ffwpress-icons', FFWP_PLUGIN_URL . "assets/css/ffwpress-icons$suffix.css");
        wp_enqueue_style('ffwpress', FFWP_PLUGIN_URL . "assets/css/ffwpress$suffix.css", ['astra-child-theme-css'], FFWP_STATIC_VERSION);

        if (!edd_is_checkout()) {
            return;
        }

        wp_enqueue_script('ffwpress-better-checkout', $this->plugin_url . "assets/js/better-checkout$suffix.js", ['jquery', 'edd-checkout-global'], FFWP_STATIC_VERSION, true);
        wp_enqueue_style('ffwpress-better-checkout', $this->plugin_url . "assets/css/better-checkout$suffix.css", ['astra-child-theme-css'], FFWP_STATIC_VERSION);
        wp_add_inline_style('ffwpress-better-checkout', $this->add_inline_stylesheet());
    }

    /**
     * Checks if debugging is enabled for local machines.
     * 
     * @return string .min | ''
     */
    public function get_script_suffix()
    {
        return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
    }

    /**
     * Dynamically load the URLs for the payment method logo's into an inline stylesheet.
     * 
     * I'm adding (and removing) the <style> block on purpose, so VS Code properly recognizes the code and formats it.
     */
    public function add_inline_stylesheet()
    {
        ob_start();
    ?>
        <style>
            <?php if (count($this->gateways) == 2) : ?>#edd-gateway-option-mollie_creditcard,
            #edd-gateway-option-mollie_ideal {
                width: 49.319%;
            }

            <?php endif; ?>#edd_payment_mode_select legend:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/powered-by-mollie.jpg'; ?>');
                width: 238px;
            }

            #edd-gateway-option-mollie_creditcard:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/mollie-credit-cards-logo.png'; ?>');
                width: 122px;
            }

            #edd-gateway-option-mollie_ideal:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/mollie-ideal-logo.png'; ?>');
                width: 40px;
            }

            #edd-gateway-option-mollie_paypal:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/mollie-paypal-logo.png'; ?>');
                width: 40px;
            }
        </style>
<?php
        return str_replace(['<style>', '</style>'], '', ob_get_clean());
    }

    /**
     * Somewhere all payment methods are lost. This functions forces them back.
     * 
     * @param mixed $gateways 
     * @return mixed 
     */
    public function force_gateways($gateways)
    {
        if (count($gateways) <= 1 && count($this->gateways) > 0) {
            return $this->gateways;
        }

        $this->gateways = $gateways;

        return $gateways;
    }
}
