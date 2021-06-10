<?php
defined('ABSPATH') || exit;

/**
 * @package   FFWP DownloadInfo Shortcodes
 * @author    Daan van den Bergh
 *            https://ffw.press
 *            https://daan.dev
 * @copyright © 2021 Daan van den Bergh
 * @license   BY-NC-ND-4.0
 *            http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

class FFWP_ProductDetailsWidget_Modify
{
    /** @var Downloads_As_Service $das */
    private $das;

    private $plugin_text_domain = 'ffwp';

    /**
     * Build class properties
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->das = new EDD_Downloads_As_Services();

        $this->init();
    }

    /**
     * Add hooks and filters.
     * 
     * @return void 
     */
    private function init()
    {
        add_filter('widget_title', [$this, 'modify_widget_title'], 10, 3);
        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_current_version'], 10, 2);
        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_date_last_updated'], 11, 2);
    }

    /**
     * Modifies widget title when download is a service.
     */
    public function modify_widget_title($title, $instance, $widget_id)
    {
        if ($title != 'Choose Your License') {
            return $title;
        }

        if ($this->das->is_service(get_the_ID())) {
            $title = __('Choose Your Package', $this->plugin_text_domain);
        }

        return $title;
    }

    /**
     * Add current version to Product Details widget.
     * 
     * @param mixed $instance 
     * @param mixed $download_id 
     * @return void 
     */
    public function add_current_version($instance, $download_id)
    {
        $current_version = get_post_meta($download_id, '_edd_sl_version', true) ?? '';
        if ($current_version) : ?>
            <div class="edd-ffw-current-version"><?= sprintf(__('Current version: <a href="#" id="ffw-changelog-popup">%s</a>', $this->plugin_text_domain), $current_version); ?></div>
        <?php endif;
    }

    /**
     * Get Readme Location defined in EDD Download.
     * 
     * @param mixed $download_id 
     * @return mixed 
     */
    public function get_changelog_url($download_id)
    {
        return get_post_meta($download_id, '_edd_readme_location', true) ?? '';
    }

    /**
     * Add Last Updated to Widget
     * 
     * @param mixed $instance 
     * @param mixed $download_id 
     * @return void 
     */
    public function add_date_last_updated($instance, $download_id)
    {
        $readme_url   = get_post_meta($download_id, '_edd_readme_location', true) ?? '';
        $headers      = get_headers($readme_url);
        $last_updated = '';

        foreach ($headers as $header) {
            if (strpos($header, 'Last-Modified') !== false) {
                $timestamp = strtotime(str_replace('Last-Modified: ', '', $header));

                $last_updated = gmdate(get_option('date_format'), $timestamp);
            }
        }

        if ($last_updated) : ?>
            <div class="edd-ffw-last-updated"><?= sprintf(__('Last updated: %s'), $last_updated); ?></div>
<?php endif;
    }
}
