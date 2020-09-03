<?php
/**
 * @formatter:off
 * Plugin Name: Easy Digital Downloads - Reusable License
 * Description: Use the same license key for a specified Easy Digital Downloads download ID.
 * Version: 1.0.0
 * Author: Daan van den Bergh (from WoOSH!)
 * Author URI: https://woosh.dev
 * Text Domain: woosh-reusable-license
 * GitHub Plugin URI: https://github.com/Dan0sz/reusable-license
 * @formatter:on
 */

/**
 * Define constants.
 */
define('WOOSH_REUSABLE_LICENSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOOSH_REUSABLE_LICENSE_PLUGIN_FILE', __FILE__);

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
function woosh_reusable_license_autoload($class)
{
    $path = explode('_', $class);

    if ($path[0] != 'WooshReusableLicense') {
        return;
    }

    if (!class_exists('FFWP_Autoloader')) {
        require_once(WOOSH_REUSABLE_LICENSE_PLUGIN_DIR . 'ffwp-autoload.php');
    }

    $autoload = new FFWP_Autoloader($class);

    return include WOOSH_REUSABLE_LICENSE_PLUGIN_DIR . 'includes/' . $autoload->load();
}

spl_autoload_register('woosh_reusable_license_autoload');

/**
 * @return WooshReusableLicense
 */
function woosh_reusable_license_init()
{
    static $wrl = null;

    if ($wrl === null) {
        $wrl = new WooshReusableLicense();
    }

    return $wrl;
}

woosh_reusable_license_init();
