<?php

/**
 * @package   FFWP Better Checkout
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_CurrentPluginVersion_Shortcode
{
    public function __construct()
    {
        add_shortcode('edd_product_version', [$this, 'add']);
    }

    /**
     * Shortcode: EDD Product Version
     * 
     * [edd_product_version]
     * You can add id="post_id_here" to display the version number of a defined product.
     */
    function add($atts)
    {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
        ), $atts);

        $args = array(
            'download_id' => get_post_meta($atts['id'], '_edd_sl_version', true),
            'download_name' =>  get_the_title($atts['id']),
        );

        $result = null;

        if (class_exists('EDD_Software_Licensing')) {
            $result = $args['download_id'];
        }

        return $result;
    }
}
