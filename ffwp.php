<?php
/**
 * @formatter:off
 * Plugin Name: Easy Digital Downloads - FFWP Modifications
 * Description: Custom additions to EDD for Fast FW Press.
 * Version: 1.5.4
 * Author: Daan (from Fast FW Press)
 * Author URI: https://ffwp.dev
 * Text Domain: ffwp
 * GitHub Plugin URI: https://github.com/Dan0sz/ffwp
 * @formatter:on
 */

/**
 * Define constants.
 */
define('FFWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FFWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FFWP_PLUGIN_FILE', __FILE__);

/**
 * Takes care of loading classes on demand.
 *
 * @param $class
 *
 * @return mixed|void
 */
function ffwp_autoload($class)
{
    $path = explode('_', $class);

    if ($path[0] != 'FFWP' || (isset($path[1]) && $path[1] == 'Autoloader')) {
        return;
    }

    if (!class_exists('FFWP_Autoloader')) {
        require_once(FFWP_PLUGIN_DIR . 'ffwp-autoload.php');
    }

    $autoload = new FFWP_Autoloader($class);

    return include FFWP_PLUGIN_DIR . 'includes/' . $autoload->load();
}

spl_autoload_register('ffwp_autoload');

/**
 * @return FFWP
 */
function ffwp_init()
{
    static $ffwp = null;

    if ($ffwp === null) {
        $ffwp = new FFWP();
    }

    return $ffwp;
}

ffwp_init();
