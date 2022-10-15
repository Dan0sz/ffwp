<?php
defined('ABSPATH') || exit;

/**
 * @package   FFWP Better Checkout
 * @author    Daan van den Bergh
 *            https://ffwp.dev
 *            https://daan.dev
 * @copyright © 2020 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */
class FFWP_BetterBlog_Enable
{
    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        add_filter('astra_post_date', [$this, 'display_date_modified']);
        add_filter('the_category_rss', [$this, 'add_featured_image_to_rss_feed']);
        add_filter('astra_featured_image_markup', [$this, 'remove_lazy_loading_attribute']);
    }

    /**
     * Remove loading="lazy" attribute from Featured Images.
     */
    public function remove_lazy_loading_attribute($thumb_html)
    {
        if (is_singular()) {
            $thumb_html = str_replace('loading="lazy"', '', $thumb_html);
        }

        return $thumb_html;
    }

    /**
     * Display date modified above posts. 
     * 
     * @param mixed $html 
     * @return mixed 
     */
    public function display_date_modified($html)
    {
        preg_match('/<span.*datePublished.*?>(?P<published>.*?)<\/span>/', $html, $date_published);
        preg_match('/<span.*dateModified.*?>(?P<modified>.*?)<\/span>/', $html, $date_modified);

        if (!isset($date_published['published']) || !isset($date_modified['modified'])) {
            return $html;
        }

        $date_published = ltrim($date_published['published']);
        $date_modified  = ltrim($date_modified['modified']);

        $published_time = strtotime($date_published);
        $modified_time  = strtotime($date_modified);

        if ($modified_time > $published_time) {
            $html = str_replace('updated', 'updated visible', $html);
            $html = str_replace($date_modified, '(updated: ' . $date_modified . ')', $html);
        }

        return $html;
    }

    /**
     * Add Featured Image as <enclosure> node to RSS feed.  
     */
    public function add_featured_image_to_rss_feed($content)
    {
        global $post;

        if (has_post_thumbnail($post->ID)) {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $img_url      = wp_get_attachment_image_src($thumbnail_id, 'post-thumbnail')[0];

            if (!$img_url) {
                return $content;
            }

            $uploads_url  = wp_get_upload_dir()['baseurl'];
            $uploads_path = wp_get_upload_dir()['basedir'];
            $img_path     = str_replace($uploads_url, $uploads_path, $img_url);

            /**
             * After migrating staging to production, files could get lost.
             */
            if (!file_exists($img_path)) {
                return $content;
            }

            $length       = filesize($img_path);
            $mime_type    = mime_content_type($img_path);
            $content      = "<enclosure url='$img_url' length='$length' type='$mime_type' />\n" . $content;
        }
        return $content;
    }
}
