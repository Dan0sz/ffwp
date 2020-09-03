<?php
/**
 * @package   FFWP
 * @author    Daan van den Bergh
 *            https://woosh.dev
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
        $this->set_reusable_license();
        $this->process_auto_add_to_cart();
        $this->set_non_required_state_field();
        $this->add_custom_checkout_fields();

        add_action('init', [$this, 'add_changelog_shortcode']);
        add_action('init', [$this, 'add_child_pages_menu']);
    }

    /**
     * @return FFWP_ReusableLicense_Set
     */
    private function set_reusable_license()
    {
        return new FFWP_ReusableLicense_Set();
    }

    /**
     * @return FFWP_AutoAddToCart_Process
     */
    private function process_auto_add_to_cart()
    {
        return new FFWP_AutoAddToCart_Process();
    }

    /**
     * @return FFWP_CustomCheckoutFields_Add
     */
    private function add_custom_checkout_fields()
    {
        return new FFWP_CustomCheckoutFields_Add();
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
     * @return FFWP_ChildPagesMenu_Add
     */
    public function add_child_pages_menu()
    {
        return new FFWP_ChildPagesMenu_Add();
    }
}