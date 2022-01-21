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
    }

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
}
