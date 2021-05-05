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
        '<strong>+ 21&#37 VAT</strong> for EU residents' => 'Excluding %1$s&#37; tax',
        'State/Province'                => 'Billing State / Province',
        'Street + House No.'            => 'Billing Address',
        'Suite, Apt No., PO Box, etc.'  => 'Billing Address Line 2 (optional)',
        'Validate'                      => 'Check',
        'Your Details'                  => 'Personal Info',
        'Zip/Postal Code'               => 'Billing Zip / Postal Code'
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

        // Modify Text Fields
        add_filter('gettext_easy-digital-downloads', [$this, 'modify_text_fields'], 1, 3);
        add_filter('gettext_edd-eu-vat', [$this, 'modify_text_fields'], 1, 3);
        add_filter('gettext_edds', [$this, 'modify_text_fields'], 1, 3);

        // Move Login Form
        remove_action('edd_purchase_form_login_fields', 'edd_get_login_fields');
        add_action('edd_checkout_form_top', 'edd_get_login_fields', -2);

        // Move User Info (Email, First and Last Name)
        remove_action('edd_purchase_form_after_user_info', 'edd_user_info_fields');
        add_action('edd_checkout_form_top', 'edd_user_info_fields');

        // Move Billing Details
        remove_action('edd_after_cc_fields', 'edd_default_cc_address_fields');
        remove_action('edd_purchase_form_after_cc_form', 'edd_checkout_tax_fields', 999);
        add_action('edd_checkout_form_top', 'edd_checkout_tax_fields', 999);

        // Overwrite Stripe Credit Card template.
        if (function_exists('edd_stripe_new_card_form')) {
            remove_action('edd_stripe_new_card_form', 'edd_stripe_new_card_form');
            add_action('edd_stripe_new_card_form', [$this, 'stripe_new_card_form']);
        }

        /**
         * When Taxes > 'Display Tax Rate' is enabled in EDD's settings, remove the mention for each
         * shopping cart item, because it seems excessive.
         */
        add_filter('edd_cart_item_tax_description', '__return_empty_string');

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
     * Display the markup for the Stripe new card form. This template override moves the Name field below the Card Number field.
     *
     * @since 2.6
     * @return void
     */
    public function stripe_new_card_form()
    {
        if (edd_stripe()->rate_limiting->has_hit_card_error_limit()) {
            edd_set_error('edd_stripe_error_limit', __('Adding new payment methods is currently unavailable.', 'edds'));
            edd_print_errors();
            return;
        }

        $split = edd_get_option('stripe_split_payment_fields', false);
    ?>
        <div id="edd-card-wrap">
            <label for="edd-card-element" class="edd-label">
                <?php
                if ('1' === $split) :
                    esc_html_e('Credit Card Number', 'edds');
                else :
                    esc_html_e('Credit Card', 'edds');
                endif;
                ?>
                <span class="edd-required-indicator">*</span>
            </label>

            <div id="edd-stripe-card-element-wrapper">
                <?php if ('1' === $split) : ?>
                    <span class="card-type"></span>
                <?php endif; ?>

                <div id="edd-stripe-card-element" class="edd-stripe-card-element"></div>
            </div>

            <p class="edds-field-spacer-shim"></p><!-- Extra spacing -->
        </div>

        <?php if ('1' === $split) : ?>

            <div id="edd-card-details-wrap">
                <p class="edds-field-spacer-shim"></p><!-- Extra spacing -->

                <div id="edd-card-exp-wrap">
                    <label for="edd-card-exp-element" class="edd-label">
                        <?php esc_html_e('Expiration', 'edds'); ?>
                        <span class="edd-required-indicator">*</span>
                    </label>

                    <div id="edd-stripe-card-exp-element" class="edd-stripe-card-exp-element"></div>
                </div>

                <div id="edd-card-cvv-wrap">
                    <label for="edd-card-exp-element" class="edd-label">
                        <?php esc_html_e('CVC', 'edds'); ?>
                        <span class="edd-required-indicator">*</span>
                    </label>

                    <div id="edd-stripe-card-cvc-element" class="edd-stripe-card-cvc-element"></div>
                </div>
            </div>

        <?php endif; ?>

        <p id="edd-card-name-wrap">
            <label for="card_name" class="edd-label">
                <?php esc_html_e('Name on the Card', 'edds'); ?>
                <span class="edd-required-indicator">*</span>
            </label>
            <span class="edd-description"><?php esc_html_e('The name printed on the front of your credit card.', 'edds'); ?></span>
            <input type="text" name="card_name" id="card_name" class="card-name edd-input required" placeholder="<?php esc_attr_e('Card name', 'edds'); ?>" autocomplete="cc-name" />
        </p>

        <div id="edd-stripe-card-errors" role="alert"></div>

    <?php
        /**
         * Allow output of extra content before the credit card expiration field.
         *
         * This content no longer appears before the credit card expiration field
         * with the introduction of Stripe Elements.
         *
         * @deprecated 2.7
         * @since unknown
         */
        do_action('edd_before_cc_expiration');
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
        wp_enqueue_style('ffwpress', FFWP_PLUGIN_URL . "assets/css/ffwpress$suffix.css");

        if (!edd_is_checkout()) {
            return;
        }

        wp_enqueue_script('ffwpress-better-checkout', $this->plugin_url . "assets/js/better-checkout$suffix.js", ['jquery', 'edd-checkout-global'], FFWP_STATIC_VERSION, true);
        wp_enqueue_style('ffwpress-better-checkout', $this->plugin_url . "assets/css/better-checkout$suffix.css", ['astra-child-theme-css', 'edd-blocks', 'edd-eu-vat', 'edd-sl-styles'], FFWP_STATIC_VERSION);
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
            #content {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/ffw-waves-grayscale.png'; ?>');
            }

            #edd_payment_mode_select legend:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/powered-by-mollie.jpg'; ?>');
                width: 238px;
            }

            #edd-gateway-option-mollie_creditcard:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/credit-card-logo.png'; ?>');
                width: 122px;
            }

            #edd-gateway-option-mollie_paypal:after {
                background-image: url('<?= FFWP_PLUGIN_URL . 'assets/images/paypal-logo.png'; ?>');
                width: 131px;
            }
        </style>
<?php
        return str_replace(['<style>', '</style>'], '', ob_get_clean());
    }
}
