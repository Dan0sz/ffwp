<?php

/**
 * @package   FFWP
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP
{
    /**
     * FFWP constructor.
     */
    public function __construct()
    {
        // $this->add_custom_checkout_fields();
        $this->modify_product_details_widget();
        $this->insert_login_fields_legend();
        $this->set_non_required_state_field();

        add_action('plugins_loaded', [$this, 'enable_better_blog']);
        add_action('plugins_loaded', [$this, 'enable_better_checkout'], 100);
        // add_action('init', [$this, 'add_changelog_shortcode']);
        add_action('init', [$this, 'add_download_info_shortcodes']);
        add_filter('login_url', [$this, 'change_login_url']);
        add_action('widgets_init', [$this, 'install_featured_image_widget']);

        // Astra Theme
        add_filter('astra_featured_image_enabled', [$this, 'disable_featured_image_on_downloads']);

        // EDD
        add_filter('edd_file_download_has_access', [$this, 'maybe_allow_download'], 10, 3);

        // Software Licensing (runs at priority 100)
        add_action('edd_add_email_tags', [$this, 'add_email_tag'], 101);
        add_filter('edd_sl_url_subdomains', [$this, 'add_local_urls']);

        // EDD EU VAT
        add_filter('edd_eu_vat_uk_hide_checkout_input', '__return_true');
        add_filter('edd_vat_current_eu_vat_rates', [$this, 'change_gb_to_zero_vat']);

        // Yoast
        add_filter('wpseo_title', [$this, 'add_shortcode_support']);

        // Rankmath
        add_filter('rank_math/frontend/title', [$this, 'add_shortcode_support']);
        add_filter('rank_math/frontend/description', [$this, 'add_shortcode_support']);
    }

    /**
     * Adds shortcode support to Yoast SEO
     * 
     * @param mixed $content 
     * @return string 
     */
    public function add_shortcode_support($content)
    {
        $content = do_shortcode($content);
        return $content;
    }

    /**
     * @return void 
     */
    public function enable_better_blog()
    {
        new FFWP_BetterBlog_Enable();
    }

    /**
     * @return FFWP_BetterCheckout_Enable
     */
    public function enable_better_checkout()
    {
        return new FFWP_BetterCheckout_Enable();
    }

    /**
     * @return FFWP_CustomCheckoutFields_Add
     */
    private function add_custom_checkout_fields()
    {
        return new FFWP_CustomCheckoutFields_Add();
    }

    /**
     * @return FFWP_ProductDetailsWidget_Modify 
     */
    private function modify_product_details_widget()
    {
        return new FFWP_ProductDetailsWidget_Modify();
    }

    /**
     * @return FFWP_LoginFieldsLegend_Insert
     */
    private function insert_login_fields_legend()
    {
        return new FFWP_LoginFieldsLegend_Insert();
    }

    /**
     * @return FFWP_NonRequiredStateField_Set
     */
    private function set_non_required_state_field()
    {
        return new FFWP_NonRequiredStateField_Set();
    }

    /**
     * @return FFWP_ChangelogShortcode_Add
     */
    public function add_changelog_shortcode()
    {
        return new FFWP_ChangelogShortcode_Add();
    }

    /**
     * @return FFWP_DownloadInfo_Shortcodes 
     */
    public function add_download_info_shortcodes()
    {
        return new FFWP_DownloadInfo_Shortcodes();
    }

    /**
     * @return FFWP_LoginUrl_Change 
     */
    public function change_login_url()
    {
        return home_url('account');
    }

    /**
     * @return void 
     */
    public function install_featured_image_widget()
    {
        $widget = new FFWP_FeaturedImageWidget_Install();

        register_widget($widget);
    }

    /**
     * Disable Featured Images on EDD pages.
     * 
     * @param mixed $featured_image 
     * @return mixed 
     */
    public function disable_featured_image_on_downloads($featured_image)
    {
        if (!astra_is_edd_page()) {
            return $featured_image;
        }

        return '';
    }

    /**
     * Custom function to allow download, because for some reason ours keep failing since EDD 3.0.
     * 
     * Checks the payment status and if the token is valid.
     */
    public function maybe_allow_download($has_access, $payment_id, $args)
    {
        $payment = edd_get_payment($payment_id);

        if (!$payment) {
            return $has_access;
        }

        $status = $payment->status;
        $deliverable_statuses = edd_get_deliverable_order_item_statuses();

        if (!in_array($status, $deliverable_statuses)) {
            return $has_access;
        }

        $parts = parse_url(add_query_arg(array()));
        wp_parse_str($parts['query'], $query_args);
        $url = add_query_arg($query_args, site_url());

        $valid_token = edd_validate_url_token($url);

        return $valid_token;
    }

    /**
     * Add the new email tag.
     * 
     * @return void 
     */
    public function add_email_tag()
    {
        new FFWP_SoftwareLicensing_Emails();
    }

    /**
     * Modify the list of subdomains to mark as local/staging.
     *
     * @param mixed $subdomains 
     * @return string[] 
     */
    public function add_local_urls($subdomains)
    {
        return array_merge(
            [
                'test.',
                '*.servebolt.cloud',
                '*.kinsta.cloud'
            ],
            $subdomains
        );
    }

    /**
     * @param array $countries 
     * @return array 
     */
    public function change_gb_to_zero_vat($countries)
    {
        $countries['GB'] = 0;

        return $countries;
    }
}
