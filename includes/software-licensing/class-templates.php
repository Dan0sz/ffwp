<?php
defined('ABSPATH') || exit;

/**
 * @package   FFWP Software Licensing
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */
class FFWP_SoftwareLicensing_Templates
{
    const FFWP_LICENSE_KEY = 'FFWP_LICENSE_MANAGER';

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
     * Init Hooks
     */
    private function init()
    {
        add_filter('edd_sl_licenses_of_purchase', [$this, 'modify_license_key']);
    }

    /**
     * Modify license key for FFW.Press License Manager. People don't need to see it
     * as it causes confusion.
     * 
     * @return void 
     */
    public function modify_license_key($licenses)
    {
        foreach ($licenses as &$license) {
            if ($license->license_key == self::FFWP_LICENSE_KEY) {
                $license->license_key = '';
                $license->key         = '';
            }
        }
        return $licenses;
    }
}
