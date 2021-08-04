<?php
defined('ABSPATH') || exit;

/**
 * @package   FFWP Login Fields Legend
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */
class FFWP_SoftwareLicensing_Emails
{
    /** @var $plugin_text_domain string */
    private $plugin_text_domain = 'ffwp';

    /**
     * Build class
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Hooks and filters
     * 
     * @return void 
     */
    private function init()
    {
        edd_add_email_tag('ffwp_license_keys', __('Show all purchased licenses (modified)', $this->plugin_text_domain), [$this, 'licenses_tag']);
    }

    /**
     * Create list of purchased plugins and licenses.
     */
    public function licenses_tag($payment_id = 0)
    {
        $keys_output  = '';
        $license_keys = edd_software_licensing()->get_licenses_of_purchase($payment_id);

        if ($license_keys) {
            foreach ($license_keys as $license) {
                $price_name  = '';

                if ($license->price_id) {
                    $price_name = " - " . edd_get_price_option_name($license->download_id, $license->price_id);
                }

                $keys_output .=  $license->get_download()->get_name() . $price_name . ".\n\rYour license key: <em>" . $license->key . "</em>\n\r";
            }
        }

        return $keys_output;
    }
}
