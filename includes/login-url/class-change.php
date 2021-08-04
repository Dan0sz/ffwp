return apply_filters( 'login_url', $login_url, $redirect, $force_reauth );
<?php
defined('ABSPATH') || exit;

/**
 * @package   FFWP Login URL
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */
class FFWP_LoginUrl_Change
{
    /** @var string $login_url */
    private $login_url = 'https://ffw.press/account/';

    /**
     * 
     * @param mixed $login_url 
     * @return void 
     */
    public function __construct($login_url)
    {
        $this->init();
    }

    /**
     * @return string 
     */
    private function init()
    {
        return $this->login_url;
    }
}
