<?php
/**
 * @package   FFWP/ChildPagesMenu
 * @author    Daan van den Bergh
 *            https://woosh.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

defined('ABSPATH') || exit;

class FFWP_ChildPagesMenu_Add
{
    /**
     * FFWP_ChildPagesMenu_Add constructor.
     */
    public function __construct()
    {
        add_shortcode('child_pages_menu', [$this, 'render']);
    }

    /**
     * If no slug is defined, it attempts to find a the parent's ID using the
     * last part of the current request URI and the defined base. If no base is
     * defined either, return empty contents.
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
                'slug' => '',
                'base' => '',
                'hide' => ''
            ],
            $atts,
            $tag
        );

        $slug = $atts['slug'] ?? '';

        if (!$slug) {
            $last_uri_part = array_filter(explode('/', $_SERVER['REQUEST_URI']));
            $slug          = end($last_uri_part);
        }

        $output = '';
        $base   = $atts['base'] ?? '';

        // Get page by defined $base + $slug.
        $page = get_page_by_path($base . '/' . $slug);

        // If $page + $slug return nothing, try just $base.
        if ($page == false) {
            $page = get_page_by_path($base);
        }

        $args = array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'post_parent'    => $page->ID,
            'order'          => 'ASC',
            'orderby'        => 'ID'
        );

        $parent = new WP_Query($args);

        if (!$parent->have_posts()) {
            return $output;
        }

        $output = "<ul class='ffwp-child-pages-menu'>";
        $hide   = $atts['hide'] ?? '';

        foreach ($parent->posts as $post) {
            $id = $post->ID;

            // Cast to array, because explode returns a string if delimiter is not found.
            if (strpos($hide, (string) $id) !== false) {
                continue;
            }

            $title = $post->post_title;
            $url   = get_permalink($id);

            $output .= "<li>";
            $output .= "<a href='$url' title='$title' class='ffwp-child-page' id='child-page-$id' href=''>$title</a>";
            $output .= "</li>";
        }

        $output .= "</ul>";

        return $output;
    }
}
