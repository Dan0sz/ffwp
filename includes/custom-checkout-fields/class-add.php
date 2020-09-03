<?php
/**
 * @package   Dan0sz/custom-checkout-fields
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_CustomCheckoutFields_Add
{
    const FFWP_CHECKOUT_FIELD_NAME_URL               = 'ffwp_edd_url';
    const FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO   = 'ffwp_edd_additional_info';
    const FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_MESSAGE = 'Please provide a valid URL as project information.';
    const FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_ID      = 'no_url_provided';

    /** @var string $plugin_text_domain */
    private $plugin_text_domain = 'ffwp';

    /** @var array $item_ids */
    private $item_ids = [ '4473', '4483', '4487', '4713', '4714', '4715' ];

    /**
     * WooshCustomCheckoutFields constructor.
     */
    public function __construct()
    {
        add_action('edd_purchase_form_before_cc_form', [$this, 'maybe_add_section'], 1000);
        add_filter('edd_purchase_form_required_fields', [$this, 'add_required_field']);
        add_action('edd_checkout_error_checks', [$this, 'validate_required_field'], 10, 2);
        add_filter('edd_payment_meta', [$this, 'save_fields']);
        add_action('edd_view_order_details_billing_after', [$this, 'add_payment_project_information'], 10, 1);
        add_filter('edd_receipt_no_files_found_text', [$this, 'add_receipt_project_information'], 10, 2);
    }

    /**
     * @return bool
     */
    private function item_in_cart()
    {
        foreach ($this->item_ids as $item_id) {
            $item_in_cart = edd_item_in_cart($item_id);

            if ($item_in_cart) {
                break;
            }
        }

        return $item_in_cart;
    }

    /**
     *
     */
    public function maybe_add_section()
    {
        if ($this->item_in_cart()) {
            $this->add_section();
        }
    }

    /**
     *
     */
    public function add_section()
    {
        ?>
        <fieldset id="edd-ffwp-project-info">
            <legend><?= __('Project Information', $this->plugin_text_domain); ?></legend>
            <p id="ffwp-edd-url-wrap">
                <label class="edd-label" for="ffwp-edd-url">
                    <?php _e('URL', $this->plugin_text_domain); ?> <span class="edd-required-indicator">*</span>
                </label>
                <span class="edd-description">
                    <?php _e('The URL of the website you want me to work on.', $this->plugin_text_domain); ?>
                </span>
                <input class="edd-input required" type="url" name="<?= self::FFWP_CHECKOUT_FIELD_NAME_URL; ?>" id="ffwp-edd-url" placeholder="<?php _e('E.g. https://yourdomain.com', $this->plugin_text_domain); ?>" />
            </p>
            <p id="ffwp-edd-additional-info-wrap">
                <label class="edd-label" for="ffwp-edd-additional-info">
                    <?= __('Additional Information', $this->plugin_text_domain); ?>
                </label>
                <span class="edd-description">
                    <?= __('Provide any additional information about the project you think is relevant.', $this->plugin_text_domain); ?>
                </span>
                <textarea class="edd-input" name="<?= self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO; ?>" id="ffwp-edd-additional-info" rows="5"></textarea>
            </p>
        </fieldset>
        <?php
    }

    /**
     * @param $required_fields
     *
     * @return array
     */
    public function add_required_field($required_fields)
    {
        if ($this->item_in_cart()) {
            $required_fields[self::FFWP_CHECKOUT_FIELD_NAME_URL] = [
                'error_id'      => self::FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_ID,
                'error_message' => __(self::FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_MESSAGE, $this->plugin_text_domain)
            ];
        }

        return $required_fields;
    }

    /**
     * @param $valid_data
     * @param $data
     */
    public function validate_required_field($valid_data, $data)
    {
        if (!$this->item_in_cart()) {
            return;
        }

        if (empty($data[self::FFWP_CHECKOUT_FIELD_NAME_URL])) {
            edd_set_error(self::FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_ID, __(self::FFWP_CHECKOUT_REQUIRED_FIELD_ERROR_MESSAGE, $this->plugin_text_domain));
        }
    }

    /**
     * @param $payment_meta
     *
     * @return mixed
     */
    public function save_fields($payment_meta)
    {
        if (isset($_POST[self::FFWP_CHECKOUT_FIELD_NAME_URL])) {
            $payment_meta[self::FFWP_CHECKOUT_FIELD_NAME_URL] = esc_url_raw($_POST[self::FFWP_CHECKOUT_FIELD_NAME_URL]);
        }

        if (isset($_POST[self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO])) {
            $payment_meta[self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO] = sanitize_textarea_field($_POST[self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO]);
        }

        return $payment_meta;
    }

    /**
     * @param $payment_id
     */
    public function add_payment_project_information($payment_id)
    {
        $payment         = new EDD_Payment($payment_id);
        $url             = $payment->get_meta()[self::FFWP_CHECKOUT_FIELD_NAME_URL] ?? '';
        $additional_info = $payment->get_meta()[self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO] ?? '';

        if ($url): ?>
        <div id="ffwp-project-information" class="postbox">
            <h3 class="hndle"><span><?= __('Project Information', $this->plugin_text_domain); ?></span></h3>
            <div class="inside edd-clearfix">
                <a target="_blank" href="<?= $url; ?>"><?= $url; ?></a>
                <p><?= $additional_info; ?></p>
            </div>
        </div>
        <?php endif;
    }

    /**
     * @param $text
     * @param $item_id
     *
     * @return string
     */
    public function add_receipt_project_information($text, $item_id)
    {
        if (!in_array($item_id, $this->item_ids)) {
            return $text;
        }

        $purchase = edd_get_purchase_session();

        if (empty($purchase)) {
            return $text;
        }

        $url = $purchase['post_data'][self::FFWP_CHECKOUT_FIELD_NAME_URL] ?? '';

        if (!$url) {
            return $text;
        }

        $additional_info = $purchase['post_data'][self::FFWP_CHECKOUT_FIELD_NAME_ADDITIONAL_INFO] ?? '';

        return "<a target='_blank' href='$url'>" . $url . '</a><br />' . $additional_info;
    }
}
