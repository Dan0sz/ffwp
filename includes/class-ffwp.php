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
    private $login_url = 'https://ffw.press/account/';

    /**
     * FFWP constructor.
     */
    public function __construct()
    {
        $this->add_custom_checkout_fields();
        $this->modify_product_details_widget();
        $this->insert_login_fields_legend();
        $this->set_non_required_state_field();
        $this->set_reusable_license();

        add_action('plugins_loaded', [$this, 'enable_better_checkout'], 100);
        add_action('init', [$this, 'add_changelog_shortcode']);
        add_action('init', [$this, 'add_download_info_shortcodes']);
        add_action('init', [$this, 'add_child_pages_menu']);
        add_filter('login_url', [$this, 'change_login_url']);

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
     * @return FFWP_BetterCheckout_Enable
     */
    public function enable_better_checkout()
    {
        return new FFWP_BetterCheckout_Enable();
    }

    /**
     * @return FFWP_ReusableLicense_Set
     */
    private function set_reusable_license()
    {
        return new FFWP_ReusableLicense_Set();
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
     * @return FFWP_ChildPagesMenu_Add
     */
    public function add_child_pages_menu()
    {
        return new FFWP_ChildPagesMenu_Add();
    }

    /**
     * @return FFWP_LoginUrl_Change 
     */
    public function change_login_url()
    {
        return $this->login_url;
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
