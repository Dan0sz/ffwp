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

    /** @var string $changelog */
    private $changelog;

    /** @var string $plugin_text_domain */
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

        // Begin table
        add_action('edd_product_details_widget_before_categories_and_tags', function () {
            echo '<table class="ffw-download-details"><tbody>';
        }, 9);

        // Table content
        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_current_version'], 10, 2);
        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_changelog_link'], 11, 2);
        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_date_last_updated'], 12, 2);

        // End table
        add_action('edd_product_details_widget_before_categories_and_tags', function () {
            echo '</tbody></table>';
        }, 12);

        add_action('edd_product_details_widget_before_categories_and_tags', [$this, 'add_changelog_popup'], 13);

        add_action('wp_footer', [$this, 'add_inline_script']);
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
            <tr>
                <td><?= __('Current version', $this->plugin_text_domain); ?></td>
                <td><span itemscope itemtype="https://schema.org/version"><?= sprintf(__('%s', $this->plugin_text_domain), $current_version); ?></span></td>
            </tr>
        <?php endif;
    }

    /**
     * @param mixed $instance 
     * @param mixed $download_id 
     * @return void 
     */
    public function add_changelog_link($instance, $download_id)
    {
        $this->changelog = get_post_meta($download_id, '_edd_sl_changelog', true) ?? '';

        if ($this->changelog) : ?>
            <tr>
                <td><?= __('Changelog', $this->plugin_text_domain); ?></td>
                <td><?= __('<a href="#" id="ffw-changelog-link">View</a>', $this->plugin_text_domain); ?></td>
            </tr>
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

        if (!$readme_url) {
            return;
        }

        $headers      = get_headers($readme_url);
        $last_updated = '';

        if (empty($headers)) {
            return;
        }

        foreach ($headers as $header) {
            if (strpos($header, 'Last-Modified') !== false) {
                $timestamp = strtotime(str_replace('Last-Modified: ', '', $header));

                $last_updated = gmdate('Y-m-d', $timestamp);
            }
        }

        if ($last_updated) : ?>
            <tr>
                <td><?= __('Last updated:', $this->plugin_text_domain); ?></td>
                <td><span itemscope itemtype="https://schema.org/dateModified"><?= sprintf(__('%s'), $last_updated); ?></span></td>
            </tr>
        <?php endif;
    }

    /**
     * 
     * @return void 
     */
    public function add_changelog_popup()
    {
        ?>
        <div style="display: none;" id="ffw-changelog-popup">
            <div class="ffw-changelog-popup-inner">
                <a href="#" id="ffw-changelog-close"><?= '⮿ ' . __('close', $this->plugin_text_domain); ?></a>
                <div class="ffw-changelog-wrapper">
                    <?= $this->changelog; ?>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * @return void 
     */
    public function add_inline_script()
    {
        if (get_post_type() !== 'download') {
            return;
        }
    ?>
        <script>
            var changelogLink = document.getElementById('ffw-changelog-link');
            var changelogClose = document.getElementById('ffw-changelog-close');

            if (changelogLink !== null) {
                changelogLink.addEventListener('click', toggleChangelog);
            }

            if (changelogClose !== null) {
                changelogClose.addEventListener('click', toggleChangelog);
            }

            function toggleChangelog() {
                var changelog = document.getElementById('ffw-changelog-popup');

                if (changelog.style.display === 'none') {
                    changelog.style.display = 'block';
                } else {
                    changelog.style.display = 'none';
                }
            }
        </script>
<?php
    }
}
