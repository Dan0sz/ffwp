<?php
/**
 * @package
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class WooshReusableLicense
{
    /** @var string $license_key */
    private $license_key = 'FFWP_LICENSE_MANAGER';

    /** @var string $item_id */
    private $item_id = '4163';

    /**
     * WooshReusableLicense constructor.
     */
    public function __construct()
    {
        add_filter('edd_sl_generate_license_key', [$this, 'set_license_key'], 10, 3);
    }

    /**
     * @param $key
     * @param $license_id
     * @param $download_id
     */
    public function set_license_key($key, $license_id, $download_id)
    {
        if ($download_id == $this->item_id) {
            return $this->license_key;
        }

        return $key;
    }
}
