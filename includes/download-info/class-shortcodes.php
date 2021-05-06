<?php

/**
 * @package   FFWP DownloadInfo Shortcodes
 * @author    Daan van den Bergh
 *            https://ffw.press
 *            https://daan.dev
 * @copyright © 2021 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_DownloadInfo_Shortcodes
{
    public function __construct()
    {
        add_shortcode('edd_dl_version', [$this, 'get_current_version']);
        add_shortcode('edd_dl_last_updated', [$this, 'get_date_last_updated']);
    }

    /**
     * Shortcode: EDD Download Version
     * 
     * [edd_dl_version id="post_id"]
     * 
     * "id": defaults to current post.
     */
    public function get_current_version($attributes)
    {
        $attributes = shortcode_atts([
            'id' => get_the_ID(),
        ], $attributes);

        return get_post_meta($attributes['id'], '_edd_sl_version', true) ?? '';
    }

    /**
     * Shortcode: EDD Product Last Updated
     * 
     * [edd_dl_last_updated id="post_id" format="date_format"]
     * 
     * "id": defaults to current post
     * "date_format": defaults to date_format option from wp_options table. 
     */
    public function get_date_last_updated($attributes)
    {
        $attributes = shortcode_atts([
            'id' => get_the_ID(),
            'format' => get_option('date_format')
        ], $attributes);

        return get_the_modified_date($attributes['format'], $attributes['id']);
    }
}
