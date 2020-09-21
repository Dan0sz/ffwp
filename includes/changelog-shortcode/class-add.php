<?php
/**
 * @package   FFWP/ChangelogShortcode
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_ChangelogShortcode_Add
{
    /**
     * FFWP_ChangelogShortcode_Add constructor.
     */
    public function __construct()
    {
        add_shortcode('changelog', [$this, 'render']);
    }

    /**
     * Renders the contents.
     *
     * @param array  $atts
     * @param null   $content
     * @param string $tag
     *
     * @return string
     */
    public function render($atts = [], $content = null, $tag = '')
    {
        $atts = array_change_key_case((array) $atts, CASE_LOWER);

        $atts = shortcode_atts(
            [
                'id' => '0',
            ],
            $atts,
            $tag
        );

        $post_meta = apply_filters('ffwp_changelog_contents', get_post_meta($atts['id'], '_edd_sl_changelog'));

        $output = "<div class='ffwp-edd-changelog'>";

        foreach ($post_meta as $meta) {
            $output .= $meta;
        }

        $output .= "</div>";

        return $output;
    }
}
